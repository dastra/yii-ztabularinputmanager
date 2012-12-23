yii-ztabularinputmanager
========================

This is a Yii parent class for a Tabular input manager

It has been built to be used in scenarios where you have a one-to-many relationship.

The interface should present an interface for collect the data of the one, and zero, one or many rows for collect the many.

Installation
------------

Download TabularInputManager.php and save it in your protected/extensions/ directory.

You can then either import the extenstion so that it is loaded globally by adding it to protected\config\main.php

```
'import' => array(
    'application.models.*',
    'application.components.*',
    'application.extensions.TabularInputManager',
),
```

Or you can simply include it where needed:
```
<?php

Yii::import('application.extensions.TabularInputManager');
class StudentManager extends TabularInputManager
...
```

Usage
-----


In this example, we are inserting a ClassRoom with many Students.

To manage the Students, we will create a StudentManager in the protected/components/ directory by extending TabularInputManager:

```
class StudentManager extends TabularInputManager
{
    protected $class='Student';

	/**
	 * Retrieve the list of Students
	 * @return array of Student objects
	 */
    public function getItems()
    {
        if (is_array($this->_items))
            return ($this->_items);
        else
            return array(
                'n0'=>new Student,
            );
    }

	/**
	 * Deletes the uneeded Students
	 * @param $model ClassRoom - the parent model
	 * @param $itemsPk array - an array of the primary keys of the child models which we want to keep
	 */
    public function deleteOldItems($model, $itemsPk)
    {
        $criteria=new CDbCriteria;
        $criteria->addNotInCondition('id', $itemsPk);
        $criteria->addCondition("classroom_id= {$model->primaryKey}");

        Student::model()->deleteAll($criteria);
    }


	/**
	 * Create a new TabularInputManager and loads the current child items
	 * @param $model ClassRoom - the parent model
	 * @return TabularInputManager the newly created TabularInputManager object
	 */
    public static function load($model)
    {
        $return= new StudentManager;
        foreach ($model->students as $item)
            $return->_items[$item->primaryKey]=$item;
        return $return;
    }

	/**
	 * Set the unsafe attributes for the child items, usually the primary key of the parent model
	 * @param $item Student - the child item
	 * @param $model ClassRoom - the parent model
	 */
    public function setUnsafeAttribute($item, $model)
    {
        $item->classroom_id = $model->primaryKey;
    }
}
```

In this class we implement the methods needed to manage the primary keys of the students, to load all students in a ClassRoom, and to delete the students.

In this example, the controller code would look like this:

```
/**
 * Update a new model.
 * If creation is successful, the browser will be redirected to the 'view' page.
 */
public function actionCreate()
{
    $model=new ClassRoom();
    $studentManager=new StudentManager();

    // Uncomment the following line if AJAX validation is needed
    // $this->performAjaxValidation($model);

    if(isset($_POST['ClassRoom']))
    {
        $model->attributes=$_POST['ClassRoom'];
        $studentManager->manage($_POST['Student']);
        if (!isset($_POST['noValidate']))
        {
            $valid=$model->validate();
            $valid=$studentManager->validate($model) && $valid;

            if($valid)
            {
                $model->save();
                $studentManager->save($model);
                $this->redirect(array('view','id'=>$model->id));
            }
        }
    }

    $this->render('create',array(
        'model'=>$model,
        'studentManager'=>$studentManager,
    ));
}
```

In the view you have to create the fields for all the students and the button for add/delete student:

```
// add student:

<?php echo CHtml::link(
    'add',
    '#',
     array(
           'submit'=>'',
           'params'=>array('Student[command]'=>'add',
           'noValidate'=>true)));?>

// delete button
<?php echo CHtml::link(
        'delete',
        '#',
        array(
            'submit'=>'',
            'params'=>array(
                'Student[command]'=>'delete',
                'Student[id]'=>$id,
                'noValidate'=>true)
            ));?>
```

For example, in your _form.php:

```
/* fields for school */

<h2>Students:</h2>
<table>
<tr>
    <th><?php echo Student::model()->getAttributeLabel('name')?></th>
    <th><?php echo Student::model()->getAttributeLabel('surname')?></th>
    <th><?php echo CHtml::link('add', '#', array('submit'=>'', 'params'=>array('Student[command]'=>'add', 'noValidate'=>true)));?></th>
</tr>
<?php foreach($descriptionManager->items as $id=>$description):?>

<?php $this->renderPartial('_formStudent', array('id'=>$id, 'model'=>$description, 'form'=>$form));?>

<?php endforeach;?>
</table>
```

And the _formStudent:

```
<tr>
    <td>
        <?php echo $form->textArea($model,"[$id]name",array('size'=>50,'maxlength'=>255)); ?>
        <?php echo $form->error($model,"title"); ?>

    </td>

    <td>
        <?php echo $form->textArea($model,"[$id]surname",array('rows'=>6, 'cols'=>50)); ?>
        <?php echo $form->error($model,"description"); ?>
    </td>

    <td><?php echo CHtml::link(
        'delete',
        '#',
        array(
            'submit'=>'',
            'params'=>array(
                'Student[command]'=>'delete',
                'Student[id]'=>$id,
                'noValidate'=>true)
            ));?>
    </td>
</tr>
```

The result will be a table of students with buttons to add and delete students.