<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeesImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation,
    SkipsOnFailure,
    WithBatchInserts,
    WithChunkReading
{
    use SkipsFailures;

    private $rows = 0;

    public function model(array $row)
    {
        ++$this->rows;

        return new Employee([
            'rfid_tag'        => $row['rfid_tag'] ?? $row['rfid'] ?? null,
            'first_name'      => $row['first_name'] ?? $row['firstname'] ?? null,
            'last_name'       => $row['last_name'] ?? $row['lastname'] ?? null,
            'birthdate'      => $this->transformDate($row['birthdate'] ?? null),
            'contact_number'  => $row['contact_number'] ?? $row['phone'] ?? null,
            'emergency_contact' => $row['emergency_contact'] ?? null,
            'email'          => $row['email'],
            'gender'         => $row['gender'] ?? 'Other',
            'street_address' => $row['street_address'] ?? $row['address'] ?? null,
            'city'           => $row['city'] ?? null,
            'state'         => $row['state'] ?? null,
            'zip_code'      => $row['zip_code'] ?? $row['postal_code'] ?? null,
            'country'       => $row['country'] ?? null,
            'status'        => $row['status'] ?? 'Active',
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:employees,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rfid_tag' => 'required|unique:employees,rfid_tag',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }
}