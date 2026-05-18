<?php

declare(strict_types=1);

namespace Vimatech\Membership\Contracts;

interface MembershipRole
{
    public function level(): int;

    public function label(): string;
}
