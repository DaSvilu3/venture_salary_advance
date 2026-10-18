<?php

namespace App\Domain\Roster;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SplFileObject;

/**
 * Adds employees from a CSV file with the header
 * staff_no,name,civil_id,email,phone,net_salary,employment_start_date. Valid rows are added and
 * invited; invalid rows are reported with their line number and nothing else is changed for them.
 */
class ImportRoster
{
    public const HEADER = ['staff_no', 'name', 'civil_id', 'email', 'phone', 'net_salary', 'employment_start_date'];

    public function __construct(private readonly AddEmployee $addEmployee) {}

    /**
     * @return array{added: int, errors: list<array{line: int, messages: list<string>}>}
     */
    public function handle(Employer $employer, string $path, User $actor): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $added = 0;
        $errors = [];
        $header = null;
        $seenStaffNumbers = [];

        foreach ($file as $index => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $row = array_map(fn ($value): string => trim((string) $value), $row);
            $line = $index + 1;

            if ($header === null) {
                $header = array_map(fn (string $column): string => strtolower(ltrim($column, "\u{FEFF}")), $row);

                if (array_diff(self::HEADER, $header) !== []) {
                    return ['added' => 0, 'errors' => [[
                        'line' => 1,
                        'messages' => [__('The first row must be the header: :columns', ['columns' => implode(',', self::HEADER)])],
                    ]]];
                }

                continue;
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
            $data = array_intersect_key($data, array_flip(self::HEADER));
            $validator = Validator::make($data, RosterRowRules::for($employer));

            if (in_array($data['staff_no'], $seenStaffNumbers, true)) {
                $validator->after(fn ($validator) => $validator->errors()->add('staff_no', __('This staff number appears twice in the file.')));
            }

            if ($validator->fails()) {
                $errors[] = ['line' => (int) $line, 'messages' => array_values($validator->errors()->all())];

                continue;
            }

            $seenStaffNumbers[] = $data['staff_no'];

            DB::transaction(fn () => $this->addEmployee->handle($employer, $validator->validated(), $actor));
            $added++;
        }

        return ['added' => $added, 'errors' => $errors];
    }
}
