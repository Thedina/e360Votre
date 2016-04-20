<?php
/**
 * new StandardView specifications
 */

interface MultiView {
    /**
     * Allows for the resulting tables to be additionally grouped into Bootstrap Panels.  This will lock in the primary
     * sorting method (columns can define the ASC/DESC).
     * @param Column $column
     * @param bool $isDefault
     * @return $this
     */
    public function panelGroupBy(Column $column, $isDefault = false);

}

interface Column {


}