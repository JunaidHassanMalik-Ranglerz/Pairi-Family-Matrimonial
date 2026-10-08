<?php

namespace App\Traits;

use ReflectionClass;

trait PermissionsTrait
{
    public static array $dashboardPermissions = [
        'group_name'  => 'dashboard',
        'permissions' => [
            'dashboard.view',
        ],
    ];

    public static array $adminProfilePermissions = [
        'group_name'  => 'admin profile',
        'permissions' => [
            'admin.profile.view',
            'admin.profile.update',
        ],
    ];

    public static array $couponManagementPermissions = [
        'group_name'  => 'coupon management',
        'permissions' => [
            'coupon.management',
        ],
    ];

    public static array $orderManagementPermissions = [
        'group_name'  => 'order management',
        'permissions' => [
            'order.management',
            'order.status.update',
            'order.payment.update',
            'order.edit-update',
            'order.delete',
        ],
    ];

    public static array $walletManagementPermissions = [
        'group_name'  => 'wallet management',
        'permissions' => [
            'wallet.management',
        ],
    ];

    public static array $clubpointManagementPermissions = [
        'group_name'  => 'clubpoint management',
        'permissions' => [
            'clubpoint.management',
        ],
    ];

    public static array $paymentWithdrawManagementPermissions = [
        'group_name'  => 'payment withdraw management',
        'permissions' => [
            'payment.withdraw.management',
        ],
    ];

    public static array $productPermissions = [
        'group_name'  => 'product',
        'permissions' => [
            'product.view',
            'product.create',
            'product.edit',
            'product.delete',
            'product.status',
            'product.bulk.import',
            'product.barcode.print',
            'product.seller.view',
        ],
    ];

    public static array $productAttributePermissions = [
        'group_name'  => 'Product attribute',
        'permissions' => [
            'product.attribute.view',
            'product.attribute.create',
            'product.attribute.store',
            'product.attribute.edit',
            'product.attribute.update',
            'product.attribute.delete',
        ],
    ];

    public static array $productCategoryPermissions = [
        'group_name'  => 'product category',
        'permissions' => [
            'product.category.view',
            'product.category.create',
            'product.category.edit',
            'product.category.update',
            'product.category.delete',
        ],
    ];

    public static array $productBrandPermissions = [
        'group_name'  => 'product brand',
        'permissions' => [
            'product.brand.view',
            'product.brand.create',
            'product.brand.edit',
            'product.brand.update',
            'product.brand.delete',
        ],
    ];

    public static array $productTagsPermissions = [
        'group_name'  => 'product tags',
        'permissions' => [
            'product.tags.view',
            'product.tags.create',
            'product.tags.edit',
            'product.tags.update',
            'product.tags.delete',
        ],
    ];

    public static array $productLabelPermissions = [
        'group_name'  => 'product label',
        'permissions' => [
            'product.label.view',
            'product.label.create',
            'product.label.edit',
            'product.label.update',
            'product.label.delete',
        ],
    ];

    public static array $productUnitPermissions = [
        'group_name'  => 'product unit',
        'permissions' => [
            'product.unit.view',
            'product.unit.create',
            'product.unit.edit',
            'product.unit.update',
            'product.unit.delete',
        ],
    ];

    public static array $productReviewsPermissions = [
        'group_name'  => 'product reviews',
        'permissions' => [
            'product.reviews.view',
            'product.reviews.update',
            'product.reviews.delete',
        ],
    ];

    public static array $adminPermissions = [
        'group_name'  => 'admin',
        'permissions' => [
            'admin.view',
            'admin.create',
            'admin.store',
            'admin.edit',
            'admin.update',
            'admin.delete',
        ],
    ];

    public static array $blogCatgoryPermissions = [
        'group_name'  => 'blog category',
        'permissions' => [
            'blog.category.view',
            'blog.category.create',
            'blog.category.translate',
            'blog.category.store',
            'blog.category.edit',
            'blog.category.update',
            'blog.category.delete',
        ],
    ];

    public static array $blogPermissions = [
        'group_name'  => 'blog',
        'permissions' => [
            'blog.view',
            'blog.create',
            'blog.translate',
            'blog.store',
            'blog.edit',
            'blog.update',
            'blog.delete',
        ],
    ];

    public static array $blogCommentPermissions = [
        'group_name'  => 'blog comment',
        'permissions' => [
            'blog.comment.view',
            'blog.comment.update',
            'blog.comment.replay',
            'blog.comment.delete',
        ],
    ];

    public static array $rolePermissions = [
        'group_name'  => 'role',
        'permissions' => [
            'role.view',
            'role.create',
            'role.store',
            'role.assign',
            'role.edit',
            'role.update',
            'role.delete',
        ],
    ];

    public static array $settingPermissions = [
        'group_name'  => 'setting',
        'permissions' => [
            'setting.view',
            'setting.update',
        ],
    ];

    public static array $basicPaymentPermissions = [
        'group_name'  => 'basic payment',
        'permissions' => [
            'basic.payment.view',
            'basic.payment.update',
        ],
    ];

    public static array $contactMessagePermissions = [
        'group_name'  => 'contact message',
        'permissions' => [
            'contact.message.view',
            'contact.message.update',
            'contact.message.delete',
        ],
    ];

    public static array $currencyPermissions = [
        'group_name'  => 'currency',
        'permissions' => [
            'currency.view',
            'currency.create',
            'currency.store',
            'currency.edit',
            'currency.update',
            'currency.delete',
        ],
    ];

    public static array $customerPermissions = [
        'group_name'  => 'customer',
        'permissions' => [
            'customer.view',
            'customer.bulk.mail',
            'customer.create',
            'customer.store',
            'customer.edit',
            'customer.update',
            'customer.delete',
        ],
    ];

    public static array $languagePermissions = [
        'group_name'  => 'language',
        'permissions' => [
            'language.view',
            'language.create',
            'language.store',
            'language.edit',
            'language.update',
            'language.delete',
            'language.translate',
            'language.single.translate',
        ],
    ];

    public static array $menuPermissions = [
        'group_name'  => 'menu builder',
        'permissions' => [
            'menu.view',
            'menu.create',
            'menu.update',
            'menu.delete',
        ],
    ];

    public static array $pagePermissions = [
        'group_name'  => 'page builder',
        'permissions' => [
            'page.view',
            'page.create',
            'page.store',
            'page.edit',
            'page.component.add',
            'page.update',
            'page.delete',
        ],
    ];

    public static array $subscriptionPermissions = [
        'group_name'  => 'subscription',
        'permissions' => [
            'subscription.view',
            'subscription.create',
            'subscription.store',
            'subscription.edit',
            'subscription.update',
            'subscription.delete',
        ],
    ];

    public static array $paymentPermissions = [
        'group_name'  => 'payment',
        'permissions' => [
            'payment.view',
            'payment.update',
        ],
    ];

    public static array $locationPermissions = [
        'group_name'  => 'location',
        'permissions' => [
            'location.view',
            'country.create',
            'country.list',
            'country.store',
            'country.edit',
            'country.update',
            'country.delete',
            'state.list',
            'state.create',
            'state.store',
            'state.edit',
            'state.update',
            'state.delete',
            'city.list',
            'city.create',
            'city.store',
            'city.edit',
            'city.update',
            'city.delete',
        ],
    ];

    public static array $socialPermission = [
        'group_name'  => 'social link management',
        'permissions' => [
            'social.link.management',
        ],
    ];

    public static array $sitemapPermission = [
        'group_name'  => 'sitemap management',
        'permissions' => [
            'sitemap.management',
        ],
    ];

    public static array $shippingPermission = [
        'group_name'  => 'shipping management',
        'permissions' => [
            'shipping.management',
        ],
    ];

    public static array $manageKycPermission = [
        'group_name'  => 'kyc management',
        'permissions' => [
            'kyc.management',
        ],
    ];

    public static array $taxPermission = [
        'group_name'  => 'tax management',
        'permissions' => [
            'tax.view',
            'tax.create',
            'tax.translate',
            'tax.store',
            'tax.edit',
            'tax.update',
            'tax.delete',
        ],
    ];

    public static array $newsletterPermissions = [
        'group_name'  => 'newsletter',
        'permissions' => [
            'newsletter.view',
            'newsletter.mail',
            'newsletter.delete',
        ],
    ];

    public static array $testimonialPermissions = [
        'group_name'  => 'testimonial',
        'permissions' => [
            'testimonial.view',
            'testimonial.create',
            'testimonial.translate',
            'testimonial.store',
            'testimonial.edit',
            'testimonial.update',
            'testimonial.delete',
        ],
    ];

    public static array $faqPermissions = [
        'group_name'  => 'faq',
        'permissions' => [
            'faq.view',
            'faq.create',
            'faq.translate',
            'faq.store',
            'faq.edit',
            'faq.update',
            'faq.delete',
        ],
    ];

    public static array $addonsPermissions = [
        'group_name'  => 'Addons',
        'permissions' => [
            'addon.view',
            'addon.install',
            'addon.update',
            'addon.status.change',
            'addon.remove',
        ],
    ];

    public static array $frontendPermissions = [
        'group_name'  => 'Frontend',
        'permissions' => [
            'frontend.view',
            'frontend.update',
        ],
    ];

    public static array $manageSellerPermissions = [
        'group_name'  => 'Sellers',
        'permissions' => [
            'sellers.view',
            'sellers.update',
            'sellers.delete',
            'sellers.status',
        ],
    ];

    /**
     * @return mixed
     */
    private static function getSuperAdminPermissions(): array
    {
        $reflection = new ReflectionClass(__TRAIT__);
        $properties = $reflection->getStaticProperties();

        $permissions = [];
        foreach ($properties as $value) {
            if (is_array($value)) {
                $permissions[] = [
                    'group_name'  => $value['group_name'],
                    'permissions' => (array) $value['permissions'],
                ];
            }
        }

        return $permissions;
    }
}
