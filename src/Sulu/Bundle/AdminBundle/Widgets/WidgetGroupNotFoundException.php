<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Widgets;

class WidgetGroupNotFoundException extends WidgetException
{
    public function __construct($message, $group)
    {
        parent::__construct($message, $group);
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'group' => $this->subject,
        ];
    }
}
