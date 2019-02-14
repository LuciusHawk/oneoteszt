<?php

use PHPUnit\Framework\TestCase;

require('Wallet.php');

class TestWallet extends TestCase
{

    public function testConstructor()
    {
        
        $wallet = new Wallet();
        $this->assertInstanceOf(Wallet::class, $wallet);

        return $wallet;
    }

    /**
     * @dataProvider addSuccessProvider
     * @depends testConstructor
     */
    public function testAddSuccess(int $money, Wallet $wallet)
    {
        # A pénztárcába különböző címletek kerülnek elhelyezésre,
        # a művelet sikerét a pénztárca add() függvényének igaz visszatérési értéke jelzi.
        $this->assertTrue($wallet->add($money));
    }

    public function addSuccessProvider()
    {
        return [
            [5],
            [10],
            [20],
            [50],
            [100],
            [200],
            [500],
            [1000],
            [2000],
            [5000],
            [10000],
            [20000],
        ];
    }

    /**
     * @dataProvider addThrowException
     * @depends testConstructor
     * @expectedException \InvalidArgumentException
     */
    public function testAddThrowException(int $money, Wallet $wallet)
    {
        # A pénztárcába különböző nem létező címletek kerülnek elhelyezésre,
        # a művelet sikertelenségét a pénztárca add() függvényében InvalidArgumentException kivétel dobása jelzi.
        $wallet->add($money);
    }

    public function addThrowException()
    {
        return [
            [0],
            [1],
            [2],
            [55],
            [101],
            [203],
            [515],
            [1024],
            [2048],
            [5050],
            [10001],
            [50000],
        ];
    }

    /**
     * @depends testConstructor
     */
    public function testGetContent(Wallet $wallet)
    {
        $wallet->add(10);
        $wallet->add(10);
        $wallet->add(10000);

        # A pénztárca tartalmának lekérdezése a getContent() függvénnyel történik.
        # Ennek visszatérési értéke a következő:
        # stdClass {
        #     change: int[0..*] Fémpénzek tételes felsorolása növekvő sorrendben
        #     paper: int[0..*] Papírpénzek tételes felsorolása növekvő sorrendben
        # }
        $content = $wallet->getContent();
        $this->assertInstanceOf(stdClass::class, $content);

        $this->assertObjectHasAttribute('change', $content);
        $this->assertInternalType('array', $content->change);
        $this->assertEquals([5, 10, 10, 10, 20, 50, 100, 200], $content->change);

        $this->assertObjectHasAttribute('paper', $content);
        $this->assertInternalType('array', $content->paper);
        $this->assertEquals([500, 1000, 2000, 5000, 10000, 10000, 20000], $content->paper);

        return $wallet;
    }

    /**
     * @depends testGetContent
     */
    public function testSum(Wallet $wallet)
    {
        # A pénztárcában lévő címletek összegének lekérdezése a sum() függvénnyel történik.
        # E függvénytől visszatérési érték gyanánt egész számot várunk
        $this->assertEquals(48905, $wallet->sum());

        return $wallet;
    }

    /**
     * @depends testSum
     */
    public function testTakeOutSuccess(Wallet $wallet)
    {
        $obj1 = new stdClass();
        $obj1->change= array(10, 10);
        $obj1->paper= array(10000, 20000);
        # A pénztárcából a takeOut() függvény segítségével meghatározott címleteket veszünk ki.
        # Ennek paramétere a következőképp néz ki:
        # stdClass {
        #     change: int[0..*] Fémpénzek tételes felsorolása növekvő sorrendben
        #     paper: int[0..*] Papírpénzek tételes felsorolása növekvő sorrendben
        # }
        # A művelet sikerét a logikai visszatérési értéke jelzi ...
        $this->assertTrue($wallet->takeOut($obj1));
        # ... Sikeres kivételkor értelemszerűen változik a pénztárca tartalma.
        $this->assertEquals(18885, $wallet->sum());
        $content = $wallet->getContent();
        $this->assertInstanceOf(stdClass::class, $content);
        $this->assertObjectHasAttribute('change', $content);
        $this->assertInternalType('array', $content->change);
        $this->assertEquals([5, 10, 20, 50, 100, 200], $content->change);
        $this->assertObjectHasAttribute('paper', $content);
        $this->assertInternalType('array', $content->paper);
        $this->assertEquals([500, 1000, 2000, 5000, 10000], $content->paper);

        $obj1 = new stdClass();
        $obj1->change= array(10, 50);
        $obj1->paper= array(20000);
        # A pénztárcából nem lehet olyan címleteket kivenni, amelyek nincsenek is benne. ...
        $this->assertFalse($wallet->takeOut($obj1));
        # ... Sikertelen kivételkor értelemszerűen nem változik a pénztárca tartalma.
        $this->assertEquals(18885, $wallet->sum());
        $content = $wallet->getContent();
        $this->assertInstanceOf(stdClass::class, $content);
        $this->assertObjectHasAttribute('change', $content);
        $this->assertInternalType('array', $content->change);
        $this->assertEquals([5, 10, 20, 50, 100, 200], $content->change);
        $this->assertObjectHasAttribute('paper', $content);
        $this->assertInternalType('array', $content->paper);
        $this->assertEquals([500, 1000, 2000, 5000, 10000], $content->paper);

        $obj1 = new stdClass();
        $obj1->change= array(5, 10, 20, 50, 100, 200);
        $obj1->paper= array(500, 1000, 2000, 5000, 10000);
        $this->assertTrue($wallet->takeOut($obj1));
        # Az üres pénztárcában fellelhető összeg éppen 0, ...
        $this->assertEquals(0, $wallet->sum());
        $content = $wallet->getContent();
        $this->assertInstanceOf(stdClass::class, $content);
        # ... valamint a getContent() révén kapott objektum attribútumainak értékei üres tömbök.
        $this->assertObjectHasAttribute('change', $content);
        $this->assertInternalType('array', $content->change);
        $this->assertEquals([], $content->change);
        $this->assertObjectHasAttribute('paper', $content);
        $this->assertInternalType('array', $content->paper);
        $this->assertEquals([], $content->paper);
    }

    /**
     * @dataProvider removableSuccessProvider
     */
    public function testRemovableSuccess(array $money, int $value, bool $removable)
    {
        $wallet = new Wallet();
        foreach ($money as $k => $v) {
            $wallet->add($v);
        }

        # A pénztárcához implementálni kell egy removable nevű függvényt,
        # melynek egy int-et argumentumul megadva képes eldönteni, hogy az
        # értéket ki lehet-e fizetni a pénztárcában rendelkezésre álló
        # címletekből pontosan, vagy nem.
        $this->assertEquals($removable, $wallet->removable($value));
    }

    public function removableSuccessProvider()
    {
        return [
            [
                [2000, 5000, 2000, 10, 5, 500, 100, 100, 100, 200, 2000],
                6000,
                true
            ],
            [
                [2000, 5000, 2000, 10, 5, 500, 100, 100, 100, 200, 2000],
                7605,
                true
            ],
            [
                [2000, 5000, 2000, 10, 5, 500, 100, 100, 100, 200, 2000],
                4420,
                false
            ],
            [
                [2000, 5000, 2000, 2000],
                6000,
                true
            ],
        ];
    }

    /**
     * @dataProvider removableThrowInvalidArgumentExceptionProvider
     * @expectedException \InvalidArgumentException
     */
    public function testRemovableThrowInvalidArgumentException(int $value)
    {
        $wallet = new Wallet();
        # Ha a removable() irreális értéket kap, amit esélytelen pontosan
        # kivenni a pénztárcából, a függvény InvalidArgumentException kivételt dob
        $wallet->removable($value);
    }

    public function removableThrowInvalidArgumentExceptionProvider()
    {
        return [
            [0], [-21], [-100], [23], [1], [2]
        ];
    }

    /**
     * @dataProvider removableThrowTooMochExceptionProvider
     * @expectedException \Exception
     * @expectedExceptionMessage Too much
     */
    public function testRemovableThrowTooMochException(array $money, int $value)
    {
        $wallet = new Wallet();
        foreach ($money as $k => $v) {
            $wallet->add($v);
        }
        # Ha a removable() túl magas értéket kap, ami meghaladja a pénztárca tartalmát,
        # a függvény Exception kivételt dob "Too much" üzenettel
        $wallet->removable($value);
    }

    public function removableThrowTooMochExceptionProvider()
    {
        return [
            [
                [2000, 2000, 10, 5, 500, 100, 100],
                6000
            ],
            [
                [2000, 2000, 10, 5, 500, 100, 100],
                4720
            ],
        ];
    }

}

