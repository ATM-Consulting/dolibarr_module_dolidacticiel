<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/dolidacticiel.lib.php
 *	\ingroup	dolidacticiel
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function dolidacticielAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("dolidacticiel@dolidacticiel");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/dolidacticiel/admin/dolidacticiel_setup.php", 1);
    $head[$h][1] = $langs->trans("Achievements");
    $head[$h][2] = 'ggwp';
    $h++;
    $head[$h][0] = dol_buildpath("/dolidacticiel/admin/dolidacticiel_others.php", 1);
    $head[$h][1] = $langs->trans("DolidacticielOthersTests");
    $head[$h][2] = 'otherstests';
    $h++;
    $head[$h][0] = dol_buildpath("/dolidacticiel/admin/dolidacticiel_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@dolidacticiel:/dolidacticiel/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@dolidacticiel:/dolidacticiel/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolidacticiel');

    return $head;
}
