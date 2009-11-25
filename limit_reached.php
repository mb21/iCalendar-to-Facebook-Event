<?php
/*
    This file is part of iCalendar-to-Facebook-Event.

    iCalendar-to-Facebook-Event is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    iCalendar-to-Facebook-Event is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with iCalendar-to-Facebook-Event.  If not, see <http://www.gnu.org/licenses/>.
*/

mb_internal_encoding('UTF-8');
require_once 'config.php';

echo '<fb:success><fb:message>To not overstretch the facebook limits, not all events will be removed simultaneously. The remainder will be done in a few seconds. You will then see the subscription removed on the <a href="' . _SITE_URL . '">iCalendar-to-Event page</a>. </fb:message></fb:success>';
?>