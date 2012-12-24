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
yii::import('application.extensions.TabularInputManager');

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
            return $this->_items;
        else {
            return array(
                'n0' => new Student,
            );
        }
    }

	/**
	 * Deletes the uneeded Students
	 * @param $model ClassRoom - the parent model
	 * @param $itemsPk array - an array of the primary keys of the child models which we want to keep
	 */
    public function deleteOldItems($model, $itemsPk) {
        $criteria = new CDbCriteria;
        $criteria->addNotInCondition('id', $itemsPk);
        //Student has a attribute classroom_id: indicates which classroom s/he is in.
        $criteria->addCondition("classroom_id = {$model->primaryKey}");

        Student::model()->deleteAll($criteria);
    }


	/**
	 * Create a new TabularInputManager and loads the current child items
	 * @param $model ClassRoom - the parent model
	 * @return TabularInputManager the newly created TabularInputManager object
	 */
    public static function load($model) {
        $return = new StudentManager;
        foreach($model->students as $item)
            $return->_items[$item->primaryKey]=$item;
        return $return;
    }

	/**
	 * Set the unsafe attributes for the child items, usually the primary key of the parent model
	 * @param $item Student - the child item
	 * @param $model ClassRoom - the parent model
	 */
    public function setUnsafeAttribute($item, $model) {
        $item->classroom_id = $model->primaryKey;
    }
}

?>
```

In this class we implement the methods needed to manage the primary keys of the students, to load all students in a ClassRoom, and to delete the students.

In this example, the controller code would look like this:

```
/**
 * Create a new model.
 * If creation is successful, the browser will be redirected to the 'view' page.
 */
public function actionCreate()
{
    $classroom = new Classroom();
    $studentManager=new StudentManager($classroom);

    // Uncomment the following line if AJAX validation is needed
    // $this->performAjaxValidation($model);

    if (isset($_POST['Classroom']))
    {
        $classroom->attributes=$_POST['Classroom'];
        $studentManager->manage($classroom, $_POST['Student']);

        if($classroom->validate() && $studentManager->validate())
        {
            // You can put additional code here
            // that sets initial values for your classroom entity
            $classroom->save();
            $studentManager->save($application);

            // Redirect to wherever you want
            $this->redirect(array('view', 'id' => $classroom->id));
        }
    }

    $this->render('create', array(
        'studentManager' => $studentManager,
        'classroom' => $classroom,
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

<?php
/* @var $this ApplicationController */
/* @var $studentManager StudentManager */
/* @var $form CActiveForm */
/* @var $classroom Classroom */
?>

<div class="form">

    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'classroom-form',
        'enableAjaxValidation' => false,
            ));
    ?>
    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <table id="students">
        <thead>
            <tr>
                <td>
                    ID
                </td>
                <td>
                    Name
                </td>
                <td>
                    <?php echo CHtml::link('Add', '#', array('onClick' => 'addStudent($(this));return false;')); ?>
                </td>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($studentManager->_items as $id => $model) {
                $this->renderPartial('_formStudent', array('id' => $id, 'model' => $model, 'form' => $form, 'this' => $this),false,true);
            }
            ?>
        </tbody>
    </table>

    <?php $this->renderPartial('_studentJs', array('studentManager' => $studentManager, 'form' => $form, 'this' => $this),false,true); ?>
    <?php echo CHtml::submitButton($classroom->isNewRecord ? 'Create' : 'Save'); ?>
<?php $this->endWidget(); ?>

</div><!-- form -->

```

And the _formStudent:

```
<?php
/* @var $id int */
/* @var $model StudentManager */
/* @var $form CActiveForm */
/* @var $this ApplicationController */
?>

<tr>
     <td>
          <?php echo $form->textField($model, "[$id]id"); ?>
     </td>
     <td>
          <?php echo $form->textField($model, "[$id]name"); ?>
     </td>
     <td>
          <?php echo CHtml::link('Delete', '#', array('onClick' => 'deleteStudent($(this));return false;')); ?>
     </td>
</tr>

```

And we need the javascript file in protected/views/classroom/_studentJs.php:

```
<?php
/*@var $studentManager StudentManager */
/*@var $form CActiveForm */
/*@var $this ApplicationController */
?>

<script type="text/javascript">
    // initializiation of counters for new elements
    var lastStudent=<?php echo $studentManager->lastNew ?>;

    // the subviews rendered with placeholders
    var trStudent=new String(<?php echo CJSON::encode($this->renderPartial('_formDetail', array('id' => 'idRep', 'model' => new Student, 'form' => $form, 'this' => $this), true, false)); ?>);


    function addStudent(button)
    {
        lastStudent++;
        button.parents('table').children('tbody').append(trStudent.replace(/idRep/g,'n'+lastStudent));
    }


    function deleteStudent(button)
    {
        button.parents('tr').detach();
    }

</script>
```

The result will be a table of students with buttons to add and delete students.