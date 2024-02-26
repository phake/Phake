<?php
/*
 * Phake - Mocking Framework
 *
 * Copyright (c) 2010-2022, Mike Lively <m@digitalsandwich.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  *  Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *  *  Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *  *  Neither the name of Mike Lively nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    Phake
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.digitalsandwich.com/
 */

declare(strict_types=1);

namespace Phake\Stubber;

use Phake;
use PHPUnit\Framework\TestCase;

/**
 * Tests the function of the StubMapper
 */
class StubMapperTest extends TestCase
{
    private Phake\Stubber\StubMapper $mapper;

    public function setUp(): void
    {
        $this->mapper = new StubMapper();
    }

    /**
     * Tests mapping matchers to answers.
     */
    public function testMappingMatchers(): void
    {
        $matcher = $this->getMockBuilder(Phake\Matchers\MethodMatcher::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $stub    = $this->getMockBuilder(Phake\Stubber\AnswerCollection::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $matcher->expects($this->any())
            ->method('matches')
            ->with('foo', ['bar', 'test'])
            ->willReturn(true);

        $matcher->expects($this->any())
            ->method('getMethod')
            ->willReturn('foo');

        $this->mapper->mapStubToMatcher($stub, $matcher);

        $arguments = ['bar', 'test'];
        $this->assertSame($stub, $this->mapper->getStubByCall('foo', $arguments));
    }

    /**
     * Tests mapping matchers to answers.
     */
    public function testMappingMatchersFailsOnNonMatch(): void
    {
        $matcher = $this->getMockBuilder(Phake\Matchers\MethodMatcher::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $stub    = $this->getMockBuilder(Phake\Stubber\AnswerCollection::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $matcher->expects($this->any())
            ->method('matches')
            ->willReturn(false);

        $this->mapper->mapStubToMatcher($stub, $matcher);

        $arguments = ['bar', 'test'];
        $this->assertNull($this->mapper->getStubByCall('foo', $arguments));
    }

    /**
     * Tests resetting a stub mapper
     */
    public function testRemoveAllAnswers(): void
    {
        $matcher = $this->getMockBuilder(Phake\Matchers\MethodMatcher::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $stub    = $this->getMockBuilder(Phake\Stubber\AnswerCollection::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $matcher->expects($this->never())
            ->method('matches');

        $this->mapper->mapStubToMatcher($stub, $matcher);

        $this->mapper->removeAllAnswers();

        $arguments = ['bar', 'test'];
        $this->assertNull($this->mapper->getStubByCall('foo', $arguments));
    }

    /**
     * Tests matches in reverse order.
     */
    public function testMatchesInReverseOrder(): void
    {
        $match_me      = $this->getMockBuilder(Phake\Matchers\MethodMatcher::class)
                            ->disableOriginalConstructor()
                            ->getMock();
        $match_me_stub = $this->getMockBuilder(Phake\Stubber\AnswerCollection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $also_matches      = $this->getMockBuilder(Phake\Matchers\MethodMatcher::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $also_matches_stub = $this->getMockBuilder(Phake\Stubber\AnswerCollection::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $also_matches->expects($this->never())
            ->method('matches');

        $also_matches->expects($this->any())
            ->method('getMethod')
            ->willReturn('foo');

        $match_me->expects($this->any())
            ->method('matches')
            ->with('foo', ['bar', 'test'])
            ->willReturn(true);

        $match_me->expects($this->any())
            ->method('getMethod')
            ->willReturn('foo');

        $this->mapper->mapStubToMatcher($also_matches_stub, $also_matches);
        $this->mapper->mapStubToMatcher($match_me_stub, $match_me);

        $arguments = ['bar', 'test'];
        $this->assertSame($match_me_stub, $this->mapper->getStubByCall('foo', $arguments));
    }

    public function testMappingParameterSetter(): void
    {
        $matcher = new Phake\Matchers\MethodMatcher('method', new Phake\Matchers\ReferenceSetter(42));
        $stub    = $this->getMockBuilder(Phake\Stubber\AnswerCollection::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $value        = 'blah';
        $arguments    = [];
        $arguments[0] =& $value;

        $this->mapper->mapStubToMatcher($stub, $matcher);

        $this->assertSame($stub, $this->mapper->getStubByCall('method', $arguments));
        $this->assertSame(42, $value);
    }
}
