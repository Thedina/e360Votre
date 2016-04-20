<?php
/**
 * Class PondGIS
 */
class LilypadGIS extends Book
{

    /** table() will be automatically generated **/

    public function sync()
    {
        return LilypadGIS_160202_create::sync($this);
    }

    public function read()
    {
        return LilypadGIS_160202_create::read($this);
    }

}