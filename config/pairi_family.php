<?php

return [
    'base_url' => env('APP_BASE_URL', env('APP_URL', 'https://ranglerz.click/piyarifamily')),

    'countries' => [
        'Pakistan', 'India', 'Bangladesh', 'Sri Lanka', 'Nepal',
        'USA', 'UK', 'Canada', 'Australia', 'UAE', 'Singapore', 'Malaysia',
    ],

    'qualifications' => [
        'Any', "Bachelor's Degree", "Master's Degree", 'PhD', 'Diploma', 'High School',
    ],

    'professions' => [
        'Any', 'Software Engineer', 'Doctor', 'Engineer', 'Teacher', 'Designer', 'Business',
    ],

    'filter_cities' => [
        'Lahore', 'Karachi', 'Islamabad', 'Multan', 'Rawalpindi', 'Faisalabad',
    ],

    'employment_types' => [
        ['value' => 'employed', 'label' => 'Employed'],
        ['value' => 'self_employed', 'label' => 'Self-Employed'],
        ['value' => 'business', 'label' => 'Business'],
    ],

    'monthly_income_ranges' => [
        'Below PKR 20,000', 'PKR 20,000 - 50,000', 'PKR 50,000 - 100,000',
        'PKR 100,000 - 200,000', 'PKR 200,000+',
    ],

    'body_types' => ['slim', 'athletic', 'average', 'heavy'],

    'complexions' => ['fair', 'wheatish', 'dusky', 'dark'],

    'religions' => [
        'Islam', 'Hinduism', 'Christianity', 'Sikhism', 'Buddhism', 'Jainism', 'Other',
    ],

    'marital_statuses' => [
        'Never Married', 'Divorced', 'Widowed', 'Separated',
    ],

    'residential_statuses' => [
        'Owned', 'Rented', 'Family Owned', 'Other',
    ],

    'mother_tongues' => [
        'Urdu', 'English', 'Punjabi', 'Sindhi', 'Pashto', 'Balochi', 'Hindi', 'Bengali', 'Other',
    ],

    'languages' => [
        'Urdu', 'English', 'Punjabi', 'Sindhi', 'Pashto', 'Balochi', 'Hindi', 'Bengali', 'Arabic',
    ],

    'genders' => [
        ['value' => 'male', 'label' => 'Male'],
        ['value' => 'female', 'label' => 'Female'],
    ],

    'otp_expiry_minutes' => 5,
    'otp_resend_seconds' => 300,
    'min_profile_photos' => 3,
    'new_profile_days' => 3,
];
