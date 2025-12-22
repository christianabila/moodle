<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace auth_oauth2;

use advanced_testcase;
use core\clock;
use core\di;
use DateInterval;
use DateInvalidOperationException;
use dml_exception;
use Generator;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the class \auth_oauth2\linked_login
 *
 * @package   auth_oauth2
 * @copyright 2025 eDaktik GmbH {@link https://www.edaktik.at/}
 * @author    Christian Abila <christian.abila@edaktik.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \auth_oauth2\linked_login
 */
#[CoversFunction('delete_expired_confirmation_tokens')]
final class linked_login_test extends advanced_testcase {
    /**
     * Expired confirmation tokens are deleted
     *
     * @param int $expirydate
     * @param int $expected
     * @return void
     * @throws dml_exception
     */
    #[DataProvider('expirydate_provider')]
    public function test_delete_expired_confirmation_tokens(int $expirydate, int $expected): void {
        $this->resetAfterTest();
        global $DB, $USER;

        $DB->insert_record(
            linked_login::TABLE,
            [
                'timecreated' => time(),
                'timemodified' => 0,
                'usermodified' => 0,
                'userid' => $USER->id,
                'issuerid' => 2,
                'email' => 'email@example.com',
                'confirmtokenexpires' => $expirydate,
            ],
        );

        linked_login::delete_expired_confirmation_tokens();

        $this->assertEquals($expected, $DB->count_records(linked_login::TABLE));
    }

    /**
     * Expiry dates provider
     *
     * @return Generator
     * @throws DateInvalidOperationException
     */
    public static function expirydate_provider(): Generator {
        yield 'expired' => [
            'expirydate' => di::get(clock::class)->now()->sub(new DateInterval('PT1M'))->getTimestamp(),
            'expected' => 0,
        ];
        yield 'future' => [
            'expirydate' => di::get(clock::class)->now()->add(new DateInterval('PT40M'))->getTimestamp(),
            'expected' => 1,
        ];
    }
}
