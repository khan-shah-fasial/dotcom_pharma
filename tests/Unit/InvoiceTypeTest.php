<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserDetails;
use App\Support\InvoiceType;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceTypeTest extends TestCase
{
    public function test_legacy_null_customer_type_falls_back_to_domestic(): void
    {
        $user = new User(['type_option' => null]);
        $user->setRelation('user_details', new UserDetails(['type_option' => null]));

        $this->assertSame(InvoiceType::DOMESTIC, InvoiceType::forUser($user));
    }

    public function test_user_type_takes_precedence_and_is_normalized(): void
    {
        $user = new User(['type_option' => ' International ']);
        $user->setRelation('user_details', new UserDetails(['type_option' => 'domestic']));

        $this->assertSame(InvoiceType::INTERNATIONAL, InvoiceType::forUser($user));
    }

    public function test_invalid_customer_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceType::normalize('export');
    }

    public function test_domestic_and_international_business_choices_do_not_overlap(): void
    {
        $this->assertSame([], array_intersect(
            array_keys(InvoiceType::DOMESTIC_PAYMENT_TERMS),
            array_keys(InvoiceType::INTERNATIONAL_PAYMENT_TERMS)
        ));
        $this->assertSame([], array_intersect(
            array_keys(InvoiceType::DOMESTIC_DELIVERY_TERMS),
            array_keys(InvoiceType::INTERNATIONAL_DELIVERY_TERMS)
        ));

        $this->assertSame('Door', InvoiceType::deliveryTermLabel('door_delivery', InvoiceType::DOMESTIC));
        $this->assertSame('FOB', InvoiceType::deliveryTermLabel('fob', InvoiceType::INTERNATIONAL));
        $this->assertNull(InvoiceType::deliveryTermLabel('fob', InvoiceType::DOMESTIC));
        $this->assertNull(InvoiceType::paymentTermLabel('credit', InvoiceType::INTERNATIONAL));
    }
}
