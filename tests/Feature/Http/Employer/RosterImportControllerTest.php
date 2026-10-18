<?php

namespace Tests\Feature\Http\Employer;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Notifications\EmployeeInvited;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RosterImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_imports_valid_rows_and_reports_invalid_ones_by_line(): void
    {
        Notification::fake();
        $hr = User::factory()->employerAdmin()->for(Employer::factory())->create();
        Employee::factory()->for($hr->employer)->create(['staff_no' => 'AN-EXISTS']);

        $csv = implode("\n", [
            "\u{FEFF}staff_no,name,civil_id,email,phone,net_salary,employment_start_date",
            'AN-1,Huda Al Lawati,12345678,huda@example.test,+968 91234567,850,2023-05-01',
            'AN-2,Talal Al Abri,23456789,talal@example.test,,640.250,2022-11-20',
            'AN-EXISTS,Duplicate Person,34567890,dup@example.test,,500,2022-01-01',
            'AN-3,Bad Email,45678901,not-an-email,,500,2022-01-01',
            'AN-1,Same Number Twice,56789012,twice@example.test,,500,2022-01-01',
        ]);

        $this->actingAs($hr)
            ->post(route('employer.employees.import'), [
                'file' => UploadedFile::fake()->createWithContent('roster.csv', $csv),
            ])
            ->assertRedirect(route('employer.employees.index'))
            ->assertSessionHas('import_result', fn (array $result): bool => $result['added'] === 2
                && array_column($result['errors'], 'line') === [4, 5, 6]);

        $this->assertSame(['AN-1', 'AN-2', 'AN-EXISTS'], $hr->employer->employees()->orderBy('staff_no')->pluck('staff_no')->all());
        $this->assertSame(640_250, $hr->employer->employees()->where('staff_no', 'AN-2')->sole()->net_salary_baisa->baisa);
        Notification::assertSentTo(new AnonymousNotifiable, EmployeeInvited::class, 2);
    }

    public function test_rejects_a_file_without_the_expected_header(): void
    {
        Notification::fake();
        $hr = User::factory()->employerAdmin()->for(Employer::factory())->create();

        $this->actingAs($hr)
            ->post(route('employer.employees.import'), [
                'file' => UploadedFile::fake()->createWithContent('roster.csv', "name,salary\nHuda,850"),
            ])
            ->assertSessionHas('import_result', fn (array $result): bool => $result['added'] === 0
                && $result['errors'][0]['line'] === 1);

        $this->assertSame(0, Employee::query()->count());
    }
}
