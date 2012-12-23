<?php
/**
 * TabularInputManager is a utility class to manage tabular input.
 * it supplies all utlity necessary for create, save models in tabular input
 */
abstract class TabularInputManager extends CComponent
{
	/**
     * The child items which we are working on.
	 * @var array
	 */
	public $_items;
	
	/**
     * the class name of the child items
	 * @var string
	 */
	protected $class;

    /**
     * Holds the ID of the last record created
     * @var int
     */
    protected $_lastNew=0;
	
	
	/**
	 * Main function of the class.
	 * Load the items from db and applies modifications
	 * @param $data object - The parent model in the relationship
	 */
	public function manage($data)
	{
		// Variable which will hold the last record created's ID
		$this->_lastNew=0;
		$classname=$this->class;
		$this->_items=array();
		foreach($data as $i => $item_post)
		{
		    // If this child is to be deleted, we jump to the next one.
			if (($i=='command')||($i=='id'))
				continue;

			if (isset($data['command'])&&isset($data["id"]))
				if (($data['command']=="delete")&&($data["id"]==$i))
					continue;
			
            // if the code is like 'nxxx', it is a new record
            if (substr($i, 0, 1)=='n')
            {
                // Create a new record
                $item=new $classname();

                // Remember the last object's id
                $this->_lastNew=substr($i, 1);
            }
            else // load from db
            {
                $pk = $i;
                $model = CActiveRecord::model($this->class);
                if (is_array($model->primaryKey))
                {
                    $pk = array();
                    foreach(array_keys($model->primaryKey) as $key)
                    {
                        $pk[$key] = $item_post[$key];
                    }
                }

                $item=$model->findByPk($pk);
            }

			$this->_items[$i]=$item;
			if(isset($data[$i]))
				$item->attributes=$data[$i];
		}

		// Adding a new child
		if (isset($data['command']))
			if ($data['command']=="add")
			{
				$newId='n'.($this->_lastNew+1);
				$item=new $classname();
				$this->_items[$newId]=$item;
			}
	}
	
	public function getLastNew()
	{
		return $this->_lastNew;
	}
	
	/**
	 * Retrieve the list of the child items
	 * @return array the items loaded
	 */
	public function getItems()
	{
		if (is_array($this->_items))
			return ($this->_items);
		else 
			return array();
	}

	/**
	 * Validates items
	 * @return boolean whether validation was successful
	 */
	public function validate()
	{
		$valid=true;
        /** @var $model CActiveRecord */
		foreach ($this->_items as $i=>$model)
		    //we want to validate all tags, even if there are errors
			$valid=$model->validate() && $valid;

        return $valid;
	}
	
	/**
	 * Saves the items in the database, and deletes those items which are no longer needed.
	 * @param $model CActiveRecord the parent object
	 */
	public function save($model)
	{
		$itemOk=array();

        // Delete the old items
		if (!$model->isNewRecord)
			$this->deleteOldItems($model, $itemOk);

        // Add the new items
        foreach ($this->_items as $i=>$item)
		{
            /** @var $item CActiveRecord */
			$this->setUnsafeAttribute($item, $model);
			$item->save();
			$itemOk[]=$item->primaryKey;
		}
	}
	
	/**
	 * Set the unsafe attributes for the child items, usually the primary key of the parent model
	 * @param $item CActiveRecord - the child item
	 * @param $model CActiveRecord - the parent model
	 */
	public abstract function setUnsafeAttribute($item, $model);
	
	/**
	 * Deletes the old child items
	 * @param $model CActiveRecord - the parent model
	 * @param $itemsPk array - an array of the primary keys of the child models which we want to keep
	 */
	public abstract function deleteOldItems($model, $itemsPk);
	
	
	/**
	 * Create a new TabularInputManager and loads the current child items
	 * @param $model CActiveRecord - the parent model
	 * @return TabularInputManager the newly created TabularInputManager object
	 */
	public abstract function load($model);
}