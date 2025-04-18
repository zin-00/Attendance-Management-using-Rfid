<?php
namespace App\Imports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportExcel implements ToCollection
{
    public $importedCount = 0;
    public $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header row

            // Ensure all required fields exist in the row
            $row = $row->pad(13, null); // Pad with null up to 13 columns

            $validator = Validator::make([
                'rfid_tag' => $row[0] ?? null,
                'first_name' => $row[1] ?? null,
                'last_name' => $row[2] ?? null,
                'email' => $row[6] ?? null,
            ], [
                'rfid_tag' => 'required|unique:employees,rfid_tag',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:employees,email',
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $index + 1,
                    'errors' => $validator->errors()->all(),
                    'data' => $row->toArray()
                ];
                continue;
            }

            try {
                Employee::create([
                    'rfid_tag' => $row[0],
                    'first_name' => $row[1],
                    'last_name' => $row[2],
                    'birthdate' => $row[3] ?? null,
                    'contact_number' => $row[4] ?? null,
                    'emergency_contact' => $row[5] ?? null,
                    'email' => $row[6],
                    'gender' => $row[7] ?? 'Other',
                    'street_address' => $row[8] ?? null,
                    'city' => $row[9] ?? null,
                    'state' => $row[10] ?? null,
                    'zip_code' => $row[11] ?? null,
                    'country' => $row[12] ?? null,
                    'status' => 'Active',
                ]);

                $this->importedCount++;

            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $index + 1,
                    'errors' => [$e->getMessage()],
                    'data' => $row->toArray()
                ];
            }
        }
    }
}