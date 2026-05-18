<?php

declare(strict_types=1);

namespace Vimatech\Membership\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Vimatech\Membership\Models\Membership;

final class MemberRemoved
{
    use Dispatchable;

    public function __construct(
        public readonly Membership $membership,
        public readonly ?Model $actor = null,
    ) {}
}
