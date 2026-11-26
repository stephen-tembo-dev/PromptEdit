<?php

namespace App\Traits;

use LogicException;

/**
 * Add this to a model to stop its rows being changed or deleted once saved.
 * We use it for money records. To correct one, you add a new row instead.
 */
trait Immutable
{
    /**
     * Laravel runs this automatically. It throws an error on any update or delete.
     */
    public static function bootImmutable(): void
    {
        static::updating(function (self $model): never {
            throw new LogicException(class_basename($model).' records are immutable and cannot be updated.');
        });

        static::deleting(function (self $model): never {
            throw new LogicException(class_basename($model).' records are immutable and cannot be deleted.');
        });
    }
}
