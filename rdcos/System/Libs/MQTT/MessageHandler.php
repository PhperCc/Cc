<?php

/**
 * MQTT Client
 *
 * An open source MQTT client library in PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2013 - 2016, sskaje (https://sskaje.me/)
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 * @package    sskaje/MQTT
 * @author     sskaje (https://sskaje.me/)
 * @copyright  Copyright (c) 2013 - 2016, sskaje (https://sskaje.me/)
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://sskaje.me/MQTT/
 */

namespace MQTT;

class MessageHandler
{

    public function connack(MQTT $MQTT, Message\CONNACK $connack_object)
    {

    }

    public function disconnect(MQTT $MQTT)
    {

    }

    public function suback(MQTT $MQTT, Message\SUBACK $suback_object)
    {

    }

    public function unsuback(MQTT $MQTT, Message\UNSUBACK $unsuback_object)
    {

    }

    public function publish(MQTT $MQTT, Message\PUBLISH $publish_object)
    {

    }

    public function puback(MQTT $MQTT, Message\PUBACK $puback_object)
    {

    }

    public function pubrec(MQTT $MQTT, Message\PUBREC $pubrec_object)
    {

    }

    public function pubrel(MQTT $MQTT, Message\PUBREL $pubrel_object)
    {

    }

    public function pubcomp(MQTT $MQTT, Message\PUBCOMP $pubcomp_object)
    {

    }

    public function pingresp(MQTT $MQTT, Message\PINGRESP $pingresp_object)
    {

    }

}

# EOF