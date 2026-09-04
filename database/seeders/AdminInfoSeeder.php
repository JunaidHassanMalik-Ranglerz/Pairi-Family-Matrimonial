<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\KnowYourClient\app\Enums\KYCStatusEnum;
use Modules\KnowYourClient\app\Models\KycType;
use Modules\Language\app\Enums\AllCountriesDetailsEnum;
use Spatie\Permission\Models\Role;

class AdminInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->countrySeeder();

        $faker = Faker::create();

        if (!Admin::first()) {
            $admin                 = new Admin;
            $admin->name           = 'John Doe';
            $admin->email          = 'admin@gmail.com';
            $admin->image          = 'uploads/website-images/admin.jpg';
            $admin->password       = Hash::make(12345678);
            $admin->is_super_admin = true;
            $admin->status         = 'active';
            $admin->save();

            $role = Role::first();
            $admin?->assignRole($role);
        }

        if (!User::first()) {
            $user                    = new User;
            $user->name              = 'John Doe';
            $user->email             = 'user@gmail.com';
            $user->email_verified_at = now();
            $user->password          = Hash::make(12345678);
            $user->bio               = $faker->text(100);
            $user->phone             = $faker->phoneNumber;
            $user->birthday          = $faker->date('Y-m-d', 'now');
            $user->gender            = 'male';
            $user->country_id        = Country::inRandomOrder()->first()->id;
            $user->state_id          = State::where('country_id', $user->country_id)->inRandomOrder()->first()->id;
            $user->city_id           = City::where('state_id', $user->state_id)->inRandomOrder()->first()->id;
            $user->zip_code          = $faker->postcode;
            $user->address           = $faker->address;
            $user->save();

            $user->addresses()->create([
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'country_id'       => $user->country_id,
                'state_id'         => $user->state_id,
                'city_id'          => $user->city_id,
                'zip_code'         => $user->zip_code,
                'address'          => $user->address,
                'walk_in_customer' => 1,
                'type'             => 'home',
                'default'          => 1,
                'status'           => 1,
            ]);

            $this->generateVendor();
        }
    }

    // private function countrySeeder()
    // {
    //     $counter = 0;
    //     foreach (AllCountriesDetailsEnum::getAll() as $country) {
    //         if ($counter >= 20) {
    //             break;
    //         }
    //         $nC       = new Country();
    //         $nC->name = $country->name;
    //         $nC->code = $country->code;
    //         $nC->save();

    //         for ($i = 0; $i < 5; $i++) {
    //             $nS             = new State();
    //             $nS->name       = fake()->state();
    //             $nS->country_id = $nC->id;
    //             $nS->save();

    //             for ($j = 0; $j < 5; $j++) {
    //                 $nCity           = new City();
    //                 $nCity->name     = fake()->city();
    //                 $nCity->state_id = $nS->id;
    //                 $nCity->save();
    //             }
    //         }

    //         $counter++;
    //     }
    // }

    /**
     * @param $limit
     */
    // private function generateVendor($limit = 7)
    // {
    //     $faker = Faker::create();

    //     $user       = new User;
    //     $user->name = 'John Vendor';

    //     $user->email             = 'vendor@gmail.com';
    //     $user->email_verified_at = now();
    //     $user->password          = Hash::make(12345678);
    //     $user->bio               = $faker->text(100);
    //     $user->phone             = $faker->phoneNumber;
    //     $user->birthday          = $faker->date('Y-m-d', 'now');
    //     $user->gender            = 'male';
    //     $user->country_id        = Country::inRandomOrder()->first()->id;
    //     $user->state_id          = State::where('country_id', $user->country_id)->inRandomOrder()->first()->id;
    //     $user->city_id           = City::where('state_id', $user->state_id)->inRandomOrder()->first()->id;
    //     $user->zip_code          = $faker->postcode;
    //     $user->address           = $faker->address;
    //     $user->save();

    //     $user->addresses()->create([
    //         'name'             => $user->name,
    //         'email'            => $user->email,
    //         'phone'            => $user->phone,
    //         'country_id'       => $user->country_id,
    //         'state_id'         => $user->state_id,
    //         'city_id'          => $user->city_id,
    //         'zip_code'         => $user->zip_code,
    //         'address'          => $user->address,
    //         'walk_in_customer' => 1,
    //         'type'             => 'home',
    //         'default'          => 1,
    //         'status'           => 1,
    //     ]);

    //     $name = $faker->name;

    //     $user->seller()->create([
    //         'banner_image'    => 'website/images/vendor_img_1.webp',
    //         'shop_name'       => $name,
    //         'shop_slug'       => str($name)->slug(),
    //         'logo_image'      => 'website/images/vendor_logo.webp',
    //         'status'          => 1,
    //         'is_verified'     => 1,
    //         'email'           => $faker->email,
    //         'phone'           => $faker->phoneNumber,
    //         'open_at'         => '09:00',
    //         'closed_at'       => '18:00',
    //         'address'         => $faker->address,
    //         'seo_title'       => $faker->company,
    //         'seo_description' => $faker->company,
    //     ]);

    //     $user->seller()->update([
    //         'verification_token' => null,
    //     ]);

    //     $user->kyc()->create([
    //         'user_id'     => $user->id,
    //         'vendor_id'   => $user->seller->id,
    //         'admin_id'    => Admin::first()->id,
    //         'kyc_type_id' => KycType::where('name', 'Passport')->first()->id ?? 2,
    //         'verified_at' => now(),
    //         'message'     => 'Please verify your account.',
    //         'file'        => 'website/images/kyc-example.jpeg',
    //         'status'      => KYCStatusEnum::APPROVED->value,
    //     ]);

    //     for ($i = 0; $i < $limit; $i++) {
    //         $vendorUser                    = new User;
    //         $vendorUser->name              = fake()->name;
    //         $vendorUser->email             = fake()->unique()->safeEmail;
    //         $vendorUser->email_verified_at = now();
    //         $vendorUser->password          = Hash::make(12345678);
    //         $vendorUser->save();

    //         $vendorUser->seller()->create([
    //             'banner_image'    => 'website/images/vendor_img_' . ($i + 1) . '.webp',
    //             'shop_name'       => fake()->company,
    //             'shop_slug'       => str(fake()->company)->slug(),
    //             'logo_image'      => 'website/images/vendor_logo.webp',
    //             'status'          => 1,
    //             'is_verified'     => 1,
    //             'email'           => $vendorUser->email,
    //             'phone'           => $faker->phoneNumber,
    //             'open_at'         => '09:00',
    //             'closed_at'       => '18:00',
    //             'address'         => $faker->address,
    //             'seo_title'       => $faker->company,
    //             'seo_description' => $faker->company,
    //         ]);

    //         $vendorUser->seller()->update([
    //             'verification_token' => null,
    //         ]);
    //     }
    // }
}
