<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Process\Process;

testCase(function () {
    staticProperty('serverProcess');
    staticProperty('driver');
    staticProperty('path');

    setUpBeforeClassOnce(function () {
        // empty the logs file.
        file_put_contents(LOGS_FILE, '');

        static::$serverProcess = new Process(['php', 'tests/Functional/run-server.php', $_ENV['HOST'], $_ENV['PORT']]);
        static::$serverProcess->start();

        $capabilities = DesiredCapabilities::chrome();
        static::$driver = RemoteWebDriver::create($_ENV['SELENIUM_SERVER'], $capabilities);

        static::$path = $path = uniqid('path');

        static::$driver->get("about:blank");
        static::$driver->executeScript(<<<JAVASCRIPT
            ws = new WebSocket('ws://{$_ENV['HOST']}:{$_ENV['PORT']}/{$path}');
            ws.onmessage = event => alert(event.data);
            ws.onerror = event => alert('error');
        JAVASCRIPT);

        sleep(1);
    });

    test(function () {
        // readyState = 1 means that connection is open.
        // see https://developer.mozilla.org/en-US/docs/Web/API/WebSocket/readyState
        $this->assertEquals(1, static::$driver->executeScript('return ws.readyState'));
    });

    test(function () {
        sleep(1);

        static::$driver->wait()->until(WebDriverExpectedCondition::alertIsPresent());

        $alertMessage = static::$driver->switchTo()->alert()->getText();
        static::$driver->switchTo()->alert()->accept();

        $this->assertEquals(
            'New WebSocketConnection to the path: /'.static::$path,
            $alertMessage
        );
    });

    $messages = [
        uniqid(), // less than 126
        str_repeat('a', 126),
        str_repeat('a', 10000),
    ];

    foreach ($messages as $message) {
        test(function () use ($message) {
            // act
            static::$driver->executeScript("ws.send('{$message}')");

            static::$driver->wait()->until(
                WebDriverExpectedCondition::alertIsPresent(),
                $message
            );

            $alertMessage = static::$driver->switchTo()->alert()->getText();
            sleep(2);
            static::$driver->switchTo()->alert()->accept();

            $this->assertEquals($message, $alertMessage);
        });
    }

    testCase(function () {
        setUpBeforeClassOnce(function () {
            static::$driver->executeScript('ws.onmessage = event => alert(event.data.length);');

            sleep(1);
        });

        $messages = [
            str_repeat('a', 65535),
            str_repeat('a', 65536),
        ];

        foreach ($messages as $message) {
            test(function () use ($message) {
                // act
                static::$driver->executeScript("ws.send('{$message}')");

                static::$driver->wait()->until(
                    WebDriverExpectedCondition::alertIsPresent(),
                );

                $alertMessage = static::$driver->switchTo()->alert()->getText();
                sleep(2);
                static::$driver->switchTo()->alert()->accept();

                $this->assertEquals(strlen($message), $alertMessage);
            });
        }

        tearDownAfterClassOnce(function () {
            static::$serverProcess->stop();
            static::$driver->close();
        });
    });
});
