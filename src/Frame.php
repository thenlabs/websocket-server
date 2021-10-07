<?php

namespace ThenLabs\WebSocketServer;

/**
 * Frame format:
 *
 * 0                   1                   2                   3
 * 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 * +-+-+-+-+-------+-+-------------+-------------------------------+
 * |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
 * |I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
 * |N|V|V|V|       |S|             |   (if payload len==126/127)   |
 * | |1|2|3|       |K|             |                               |
 * +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
 * |     Extended payload length continued, if payload len == 127  |
 * + - - - - - - - - - - - - - - - +-------------------------------+
 * |                               |Masking-key, if MASK set to 1  |
 * +-------------------------------+-------------------------------+
 * | Masking-key (continued)       |          Payload Data         |
 * +-------------------------------- - - - - - - - - - - - - - - - +
 * :                     Payload Data continued ...                :
 * + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
 * |                     Payload Data continued ...                |
 * +---------------------------------------------------------------+
 *
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class Frame
{
    public const OPCODE_CONTINUATION = 0x0;
    public const OPCODE_TEXT         = 0x1;
    public const OPCODE_BINARY       = 0x2;
    public const OPCODE_CLOSE        = 0x8;
    public const OPCODE_PING         = 0x9;
    public const OPCODE_PONG         = 0xA;

    /**
     * we are using know values because in this implementation the RSV bits
     * always are equal to zero.
     */
    public const BYTE1_VALUES = [
        0 => [ // FIN = 0
            self::OPCODE_CONTINUATION => 0b00000000, // FIN = 0, RSV = 000, OPCODE = 0000
            self::OPCODE_TEXT         => 0b00000001, // FIN = 0, RSV = 000, OPCODE = 0001
            self::OPCODE_BINARY       => 0b00000011, // FIN = 0, RSV = 000, OPCODE = 0011
            self::OPCODE_CLOSE        => 0b00001000, // FIN = 0, RSV = 000, OPCODE = 1000
            self::OPCODE_PING         => 0b00001001, // FIN = 0, RSV = 000, OPCODE = 1001
            self::OPCODE_PONG         => 0b00001010, // FIN = 0, RSV = 000, OPCODE = 1010
        ],
        1 => [ // FIN = 1
            self::OPCODE_CONTINUATION => 0b10000000, // FIN = 1, RSV = 000, OPCODE = 0000
            self::OPCODE_TEXT         => 0b10000001, // FIN = 1, RSV = 000, OPCODE = 0001
            self::OPCODE_BINARY       => 0b10000011, // FIN = 1, RSV = 000, OPCODE = 0011
            self::OPCODE_CLOSE        => 0b10001000, // FIN = 1, RSV = 000, OPCODE = 1000
            self::OPCODE_PING         => 0b10001001, // FIN = 1, RSV = 000, OPCODE = 1001
            self::OPCODE_PONG         => 0b10001010, // FIN = 1, RSV = 000, OPCODE = 1010
        ],
    ];

    protected $fin = 1;
    protected $opcode = 1;
    protected $mask = 0;
    protected $payload = '';

    public function getFin(): int
    {
        return $this->fin;
    }

    public function setFin(bool $fin): void
    {
        $this->fin = $fin;
    }

    public function getOpode(): int
    {
        return $this->opcode;
    }

    public function setOpcode(int $opcode): void
    {
        $this->opcode = $opcode;
    }

    public function getMask(): int
    {
        return $this->mask;
    }

    public function setMask(bool $mask): void
    {
        $this->mask = $mask;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * This method returns the frame ready for send to the client(without masking).
     *
     * @return string
     */
    public function __toString()
    {
        // the first byte has the bits: FIN(1 bit), RSV1-3(3 bits), OPCODE(4 bits)
        // In this implementation RSV1-3 always will be 000.
        // 128 => 0b10000000
        $firstByte = $this->fin ? 128 : 0;

        // assign the opcode bits into the first byte.
        $firstByte |= $this->opcode;

        $result = chr($firstByte);

        // the second byte has the bits: MASK(1 bit), PAYLOAD LENGTH(7 bits)
        $secondByte = /* $this->mask ? 128 : */ 0;

        $payloadLength = strlen($this->payload);

        if ($payloadLength <= 125) {
            $secondByte |= $payloadLength;
            $result .= chr($secondByte);
        } elseif ($payloadLength <= 65535) {
            $secondByte |= 126;
            $result .= chr($secondByte);
            $result .= pack('n', $payloadLength);
        } else {
            $secondByte |= 127;
            $result .= chr($secondByte);
            $result .= pack('NN', 0, $payloadLength);
        }

        $result .= $this->payload;

        return $result;
    }

    public static function createFromString(string $string): ?self
    {
        $firstByte = ord($string[0]);

        $fin = null;
        $opcode = null;

        $opcode = array_search($firstByte, self::BYTE1_VALUES[1]);

        if (is_int($opcode)) {
            $fin = 1;
        } else {
            $opcode = array_search($firstByte, self::BYTE1_VALUES[0]);

            if (is_int($opcode)) {
                $fin = 0;
            }
        }

        if (false === $opcode) {
            return null;
        }

        $secondByte = ord($string[1]);

        $isMasked = $secondByte >> 7; // will be 1 or 0.

        $payloadLength = abs($secondByte - 128);
        $extendedPayloadLength = null;

        if ($payloadLength == 126) {
            $extendedPayloadLength = ord($string[2]) << 8;
            $extendedPayloadLength += ord($string[3]);
        } elseif ($payloadLength == 127) {
            $extendedPayloadLength = ord($string[2]) << 56;
            $extendedPayloadLength += ord($string[3]) << 48;
            $extendedPayloadLength += ord($string[4]) << 40;
            $extendedPayloadLength += ord($string[5]) << 32;
            $extendedPayloadLength += ord($string[6]) << 24;
            $extendedPayloadLength += ord($string[7]) << 16;
            $extendedPayloadLength += ord($string[8]) << 8;
            $extendedPayloadLength += ord($string[9]);
        }

        $payload = null;

        if ($isMasked) {
            $maskingKey = null;
            $encodedPayload = null;

            if ($payloadLength < 126) {
                $maskingKey = substr($string, 2, 5);
                $encodedPayload = substr($string, 6);
            } elseif ($payloadLength == 126) {
                $maskingKey = substr($string, 4, 7);
                $encodedPayload = substr($string, 8);
            } elseif ($payloadLength == 127) {
                $maskingKey = substr($string, 10, 13);
                $encodedPayload = substr($string, 14);
            }

            $payload = $encodedPayload;

            for ($i = 0; $i < strlen($encodedPayload); $i++) {
                $payload[$i] = $encodedPayload[$i] ^ $maskingKey[$i % 4];
            }
        }

        $frame = new self();
        $frame->setFin($fin);
        $frame->setOpcode($opcode);
        $frame->setMask($isMasked);
        $frame->setPayload($payload);

        return $frame;
    }
}
