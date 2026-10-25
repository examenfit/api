<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vinkla\Hashids\Facades\Hashids;

// Bookmarked links contain these ids; a package or config change must never alter them.
// The expected values were generated on Laravel 12 / vinkla/hashids 13 (config/hashids.php, salt in the repo).
class HashidsTest extends TestCase
{
    public function test_ids_encode_to_fixed_strings()
    {
        $expected = [
            1 => 'dNRlx',
            2 => 'pNQ8O',
            3 => 'YN8ae',
            10 => 'XN9be',
            100 => 'NkRYO',
            1234 => 'OdoLx',
            123456 => 'K0r3J',
            9999999 => '5jn6k5',
        ];

        foreach ($expected as $id => $hash) {
            $this->assertSame($hash, Hashids::encode($id), "encode($id)");
            $this->assertSame([$id], Hashids::decode($hash), "decode($hash)");
        }
    }

    public function test_ids_seen_on_staging_still_decode()
    {
        $this->assertSame([22], Hashids::decode('pOpLO')); // exam on staging
        $this->assertSame([10], Hashids::decode('XN9be')); // stream
        $this->assertSame([6], Hashids::decode('JxwMN'));
    }
}
