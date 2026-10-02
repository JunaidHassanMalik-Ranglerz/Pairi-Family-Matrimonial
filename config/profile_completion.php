<?php

return [
    'discount_percent' => 50,
    'discount_reason' => 'profile_completion',
    'discountable_plan_types' => ['Basic', 'VIP', 'VVIP'],

    /*
    | Required onboarding fields. profile_completed is true only when
    | every required field and every optional field below is filled.
    */
    'required_fields' => [
        'name',
        'email',
        'phone',
        'country',
        'gender',
        'birthday',
        'photos',
    ],

    'optional_fields' => [
        // 'bio',
        // 'city',
        'qualification',
        'field_of_study',
        'university',
        'graduation_year',
        'employment_type',
        'job_title',
        'company',
        'monthly_income',
        'residential_status',
        'height',
        'weight',
        'body_type',
        'complexion',
        'religion',
        'community',
        'sect',
        'mother_tongue',
        'other_languages',
        // 'interests',
        'marital_status',
    ],
];
