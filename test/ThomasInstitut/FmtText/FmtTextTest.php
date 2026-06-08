<?php

namespace ThomasInstitut\FmtText;

use PHPUnit\Framework\TestCase;

class FmtTextTest extends TestCase
{
    public function testNormalizeTokenText(): void
    {
        $token = new FmtTextTextToken();
        $token->text = 'hi';
        $token->fontStyle = FontStyle::Normal;
        $token->fontWeight = FontWeight::Normal;
        $token->verticalAlign = VerticalAlign::Baseline;
        $token->textDirection = TextDirection::Default;
        $token->fontSize = 1.0;
        $token->classList = null;

        $normalized = FmtText::normalizeToken($token);

        $this->assertInstanceOf(FmtTextTextToken::class, $normalized);
        $this->assertNull($normalized->fontStyle);
        $this->assertNull($normalized->fontWeight);
        $this->assertNull($normalized->verticalAlign);
        $this->assertNull($normalized->textDirection);
        $this->assertNull($normalized->fontSize);
    }

    public function testGetCompactTokenText(): void
    {
        $token = new FmtTextTextToken();
        $token->text = 'hi';
        // all other fields null

        $compact = FmtText::getCompactToken($token);
        $this->assertSame('hi', $compact);

        $token->fontWeight = FontWeight::Bold;
        $compact = FmtText::getCompactToken($token);
        $this->assertSame($token, $compact);
    }

    public function testGetCompactTokenGlue(): void
    {
        $token = new FmtTextGlueToken();
        // all other fields null

        $compact = FmtText::getCompactToken($token);
        $this->assertSame(' ', $compact);

        $token->width = 10.0;
        $compact = FmtText::getCompactToken($token);
        $this->assertSame($token, $compact);
    }

    public function testToCompactFmtText(): void
    {
        $t1 = new FmtTextTextToken();
        $t1->text = 'hello';

        $t2 = new FmtTextGlueToken();

        $t3 = new FmtTextTextToken();
        $t3->text = 'world';

        $t4 = new FmtTextTextToken();
        $t4->text = '!';
        $t4->fontWeight = FontWeight::Bold;

        $fmtText = [$t1, $t2, $t3, $t4];

        $compact = FmtText::toCompactFmtText($fmtText);

        // expected: ["hello world", $t4_normalized]
        $this->assertIsArray($compact);
        $this->assertCount(2, $compact);
        $this->assertSame('hello world', $compact[0]);
        $this->assertSame($t4, $compact[1]);
        $this->assertNull($t4->fontStyle); // normalized
    }

    public function testToCompactFmtTextSingleString(): void
    {
        $t1 = new FmtTextTextToken();
        $t1->text = 'hello';

        $compact = FmtText::toCompactFmtText([$t1]);
        $this->assertSame('hello', $compact);
    }
}
