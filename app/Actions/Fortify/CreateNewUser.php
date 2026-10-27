<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $email = mb_strtolower(trim($input['email']));
        $user = User::create([
            'name' => $input['name'],
            'email' => $email,
            'password' => $input['password'],
        ]);
        $customer = Customer::query()->whereNull('user_id')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first() ?? new Customer;
        $customer->fill(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email])->save();

        return $user;
    }
}
