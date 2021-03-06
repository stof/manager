<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Conflict;

use Exception;
use RuntimeException;

/**
 * Thrown when two packages have conflicting path mappings.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageConflictException extends RuntimeException
{
    public static function forPathConflict(PackageConflict $conflict, Exception $cause = null)
    {
        $packageNames = $conflict->getPackageNames();
        $lastPackageName = array_pop($packageNames);

        return new static(sprintf(
            "The packages \"%s\" and \"%s\" add resources for the same path ".
            "\"%s\", but have no override order defined between them.\n\n".
            "Resolutions:\n\n(1) Add the key \"override\" to the puli.json ".
            "of one package and set its value to the other package name.\n(2) ".
            "Add the key \"override-order\" to the puli.json of the root ".
            "package and define the order of the packages there.",
            implode('", "', $packageNames),
            $lastPackageName,
            $conflict->getConflictingToken()
        ), 0, $cause);
    }
}
