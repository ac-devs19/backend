<?php

namespace App\Exports;

use App\Models\Information;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];

        $students = Information::whereHas('student')
            ->get();

        foreach ($students as $info) {
            $data[] = [
                'student_number' => $info->student->student_number,
                'last_name' => $info->last_name,
                'first_name' => $info->first_name,
                'middle_name' => $info->middle_name,
                'gender' => $info->gender,
                'email_address' => $info->email_address,
                'contact_number' => $info->contact_number,
                'course' => $info->student->course,
                'student_type' => $info->student->student_type,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'student_number',
            'last_name',
            'first_name',
            'middle_name',
            'gender',
            'email_address',
            'contact_number',
            'course',
            'student_type',
        ];
    }
}
