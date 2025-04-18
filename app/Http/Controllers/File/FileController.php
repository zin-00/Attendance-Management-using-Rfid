<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FileController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $import = new EmployeesImport();
            Excel::import($import, $request->file('file'));
            
            $response = [
                'success' => true,
                'message' => 'Import completed successfully',
                'imported_count' => $import->getRowCount(),
            ];

            if ($import->failures()->isNotEmpty()) {
                $response['message'] = 'Import completed with some errors';
                $response['failures'] = $import->failures()->toArray();
            }

            return response()->json($response);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }
}