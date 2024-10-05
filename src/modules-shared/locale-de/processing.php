<?
    function weekday_abbrev(int $weekday) {
        assert($weekday >= 0 && $weekday < 7);

        $abbrevs = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
        return $abbrevs[$weekday];
    }
?>