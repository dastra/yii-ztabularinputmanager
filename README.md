yii-ztabularinputmanager
========================

This is a Yii parent class for a Tabular input manager

It has been tought to be used in scenarios where you have a one-to-many relationship.

The interface should present an interface for collect the data of the one, and zero, one or many rows for collect the many.

In the hipotesys that we have to insert a ClassRoom with many Students, we will create a StudentManager by extending Tabular input manager

<pre><code>
<?php
class StudentManager extends TabularInputManager
{

    protected $class='Student';

    public function getItems()
    {
        if (is_array($this->_items))
            return ($this->_items);
        else
            return array(
                'n0'=>new Student,
            );
    }

    public function deleteOldItems($model, $itemsPk)
    {
        $criteria=new CDbCriteria;
        $criteria->addNotInCondition('id', $itemsPk);
        $criteria->addCondition("class_id= {$model->primaryKey}");

        Student::model()->deleteAll($criteria);
    }


    public static function load($model)
    {
        $return= new StudentManager;
        foreach ($model->students as $item)
            $return->_items[$item->primaryKey]=$item;
        return $return;
    }


    public function setUnsafeAttribute($item, $model)
    {
        $item->class_id=$model->primaryKey;
    }
}
</code></pre>

In this class we implement all methods needed for manage the primary keys of the students, for load the student of a class, for delete students.

The typical controller code for use this manager is:

<pre><code>
/**
 * Update a new model.
 * If creation is successful, the browser will be redirected to the 'view' page.
 */
public function actionCreate()
{
    $model=new ClassRoom;
    $studentManager=new studentManager();

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
</pre></code>

In the view you have to create the fields for all the students and the button for add/delete student:

<pre><code>
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
</pre></code>

For example, in your _form.php:

<pre><code>
[ ... fields for school ... ]

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
</pre></code>

And the _formStudent:

<pre><code>
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
</pre></code>

The result will be a table of students, with the button for add and delete students

