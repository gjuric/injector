<?php
declare(strict_types=1);

namespace Zalas\Injector\Tests\PhpDocumentor\Fixtures;

class MissingTypeExample
{
    /**
     * @inject
     */
    private $fooWithNoServiceIdAndVar;
}