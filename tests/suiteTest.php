<?php

namespace samerton\i18next\Test;

use PHPUnit\Framework\TestCase;
use samerton\i18next\i18next;

final class i18nextTest extends TestCase {

    private function setupTest() {
        return new i18next('en', 'example/');
    }

    public function testInitFail() {
        $this->expectException(\Exception::class);

        new i18next('en', 'not found');
    }

    public function testBasics() {
        $instance = $this->setupTest();

        // Simple
        $this->assertSame('dog', $instance->getTranslation('animal.dog'));
        $this->assertSame('A friend', $instance->getTranslation('friend'));

        // With count
        $this->assertSame('1 cat', $instance->getTranslation('animal.catWithCount', ['count' => 1]));
    }

    public function testPlural() {
        $instance = $this->setupTest();

        // Simple plural
        $this->assertSame('dogs', $instance->getTranslation('animal.dog', ['count' => 2]));
    }

    public function testModifiers() {
        $instance = $this->setupTest();

        // Plural with language override
        $this->assertSame('koiraa', $instance->getTranslation('animal.dog', ['count' => 2, 'lng' => 'fi']));

        // Context
        $this->assertSame('A girlfriend', $instance->getTranslation('friend', ['context' => 'female']));

        // Context with plural
        $this->assertSame('100 girlfriends', $instance->getTranslation('friend', ['context' => 'female', 'count' => 100]));

        // Multiline object
        $this->assertSame(19, count($instance->getTranslation('animal.thedoglovers', ['returnObjectTrees' => true])));
    }

}