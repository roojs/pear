<?php
/**
 * Copyright (c) 2026 RooJ Solutions
 *
 * Based on the PHPWord writer (Copyright (c) 2011 PHPWord).
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2026 RooJ Solutions
 * @license    https://www.gnu.org/licenses/lgpl-3.0.html    LGPL-3.0
 */

interface Document_Excel_Writer_IWriter
{
    function save($filename);
}
