<?php

namespace tests;


use PHPUnit\Framework\TestCase;
use function \wordSplit;

class WordSplitTest extends TestCase {
  public function testDummy() {
    self::assertEquals(4, 2 + 2);

  }

  public function testWordSplit() {
    self::assertEquals(['hello', 'world'], wordSplit('hello    world'));
    self::assertEquals(['hello', 'world'], wordSplit('hello  .,  world'));
    self::assertEquals(['hello', 'world'], wordSplit('hello::world'));
    self::assertEquals(['hello', 'world123'], wordSplit('hello::world123'));
    self::assertEquals(['hello', 'свят1'], wordSplit('hello свят1'));

  }

  public function testWordSplitDashUnderscore() {
//    self::markTestSkipped();
    self::assertEquals(['hello', 'world_123'], wordSplit('hello::world_123'));
    self::assertEquals(['hello', 'world-123'], wordSplit('hello world-123'));
    self::assertEquals(['hello', '_свят'], wordSplit('hello _свят'));
    self::assertEquals(['hello', '-свят'], wordSplit('hello -свят'));
    self::assertEquals(['hello', 'свят_'], wordSplit('hello свят_'));


  }


}
