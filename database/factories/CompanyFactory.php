<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Setting\CompanyProfile;
use App\Models\User;
use App\Services\CompanyDefaultService;
use Database\Factories\Accounting\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => User::factory(),
            'personal_company' => true,
        ];
    }

    public function withCompanyProfile(): self
    {
        return $this->afterCreating(function (Company $company) {
            CompanyProfile::factory()->forCompany($company)->create();
        });
    }

    /**
     * Set up default settings for the company after creation.
     */
    public function withCompanyDefaults(): self
    {
        return $this->afterCreating(function (Company $company) {
            DB::transaction(function () use ($company) {
                $countryCode = $company->profile->country;
                $companyDefaultService = app(CompanyDefaultService::class);
                $companyDefaultService->createCompanyDefaults($company, $company->owner, 'USD', $countryCode, 'en');
            });
        });
    }

    public function withTransactions(int $count = 2000): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $defaultBankAccount = $company->default->bankAccount;

            TransactionFactory::new()
                ->forCompanyAndBankAccount($company, $defaultBankAccount)
                ->count($count)
                ->createQuietly([
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }
}
