<?php
class Form{
    static $pears=array();
    
    static $form;
    static $formInputs=array();
    
    /*****  Below Only For PEAR::QuickForm  *****/
    
    function create( $name='frm', $method='post' , $action='' ){
        $form = Form::createFormObject( $name, $method, $action );

        return $form;
    }
    function createFormObject( $name, $method, $action ){
        /*
        LoadVendors("formbuilder/class.form");
        
        $form = new FormBuilder($name);
        $form->setAttributes(array(
            "method" => $method,
            "action" => $action,
            "jsIncludesPath" => WEBROOT .'vendors/formbuilder/includes/',
        ));
        */
        APP::load('pear', 'HTML/QuickForm');
        APP::load('pear', 'HTML/QuickForm/advmultiselect');
        
        $form=new HTML_QuickForm($name, $method, $action );
        return $form;
    }
    function buttons( $hasName=true ){
        $buttons=array();
        if( $hasName ){
            $buttons[] = &HTML_QuickForm::createElement('submit', 'commit', '送出', array('class'=>'submit-green'));
            $buttons[] = &HTML_QuickForm::createElement('reset' , '',       '重設', array('class'=>'submit-gray'));
            //取消鍵使用button不使用submit的原因，是因為QuickForm在送出時會進行require檢查，造成取消之前還要先填好必填欄位
            $buttons[] = &HTML_QuickForm::createElement('html', '<input type="hidden" name="" value="cancel" class="hidden-cancel">');
            $buttons[] = &HTML_QuickForm::createElement('button', '', '取消', array('class'=>'submit-gray','onclick'=>"$('.hidden-cancel').attr('name', 'cancel');this.form.submit();"));
            return $buttons;
        }
        $buttons[] = &HTML_QuickForm::createElement('submit', '', '送出', array('class'=>'submit-green'));
        $buttons[] = &HTML_QuickForm::createElement('reset' , '', '重設', array('class'=>'submit-gray'));
        $buttons[] = &HTML_QuickForm::createElement('html', '<input type="hidden" name="" value="cancel" class="hidden-cancel">');
        $buttons[] = &HTML_QuickForm::createElement('button', '', '取消', array('class'=>'submit-gray','onclick'=>"$('.hidden-cancel').attr('name', 'cancel');this.form.submit();"));
        return $buttons;
    }
    function buttonsNoReset( $hasName=true ){
        $buttons=array();
        if( $hasName ){
            $buttons[] = &HTML_QuickForm::createElement('submit', 'commit', '送出', array('class'=>'submit-green'));
            //取消鍵使用button不使用submit的原因，是因為QuickForm在送出時會進行require檢查，造成取消之前還要先填好必填欄位
            $buttons[] = &HTML_QuickForm::createElement('html', '<input type="hidden" name="" value="cancel" class="hidden-cancel">');
            $buttons[] = &HTML_QuickForm::createElement('button', '', '取消', array('class'=>'submit-gray','onclick'=>"$('.hidden-cancel').attr('name', 'cancel');this.form.submit();"));
            return $buttons;
        }
        $buttons[] = &HTML_QuickForm::createElement('submit', '', '送出', array('class'=>'submit-green'));
        $buttons[] = &HTML_QuickForm::createElement('html', '<input type="hidden" name="" value="cancel" class="hidden-cancel">');
        $buttons[] = &HTML_QuickForm::createElement('button', '', '取消', array('class'=>'submit-gray','onclick'=>"$('.hidden-cancel').attr('name', 'cancel');this.form.submit();"));
        return $buttons;
    }
    function buttonsNoCancel( $hasName=true ){
        $buttons=array();
        if( $hasName ){
            $buttons[] = &HTML_QuickForm::createElement('submit', 'commit', '送出', array('class'=>'submit-green'));
            $buttons[] = &HTML_QuickForm::createElement('reset' , '',       '重設', array('class'=>'submit-gray'));
            return $buttons;
        }
        $buttons[] = &HTML_QuickForm::createElement('submit', '', '送出', array('class'=>'submit-green'));
        $buttons[] = &HTML_QuickForm::createElement('reset' , '', '重設', array('class'=>'submit-gray'));
        return $buttons;
    }
    function buttonsSubmitOnly( $hasName=true ){
        $buttons=array();
        if( $hasName ){
            $buttons[] = &HTML_QuickForm::createElement('submit', 'commit', '送出', array('class'=>'submit-green'));
            return $buttons;
        }
        $buttons[] = &HTML_QuickForm::createElement('submit', '', '送出', array('class'=>'submit-green'));
        return $buttons;
    }
    function buttonsSearchForm( $hasName=true ){
        $buttons=array();
        if( $hasName ){
            $buttons[] = &HTML_QuickForm::createElement('submit', 'commit', '送出', array('class'=>'submit-green'));
            $buttons[] = &HTML_QuickForm::createElement('reset' , '',       '重設', array('class'=>'submit-gray'));
            $buttons[] = &HTML_QuickForm::createElement('button', '',       '清除', array('class'=>'submit-gray', 'onclick'=>"javascript: former.clear('#'+this.form.id)"));
            return $buttons;
        }
        $buttons[] = &HTML_QuickForm::createElement('submit', '', '送出', array('class'=>'submit-green'));
        $buttons[] = &HTML_QuickForm::createElement('reset' , '', '重設', array('class'=>'submit-gray'));
        $buttons[] = &HTML_QuickForm::createElement('button', '', '清除', array('class'=>'submit-gray', 'onclick'=>"javascript: former.clear('#'+this.form.id)"));
        return $buttons;
    }
    
    //棄用此函數，為了安全性考量，列表由開發者自行提供
    function getFieldsList( $form ){
        //取得form表單已註冊的欄位表
        $elements = $form->_elements;
        
        $fields=array();
        foreach( $elements as $obj ){
            switch( $obj->_type ){
                case 'hidden':
                    if( $obj->_attributes['name']==='MAX_FILE_SIZE' ){ continue; }
                case 'text':
                case 'textarea':
                case 'link':
                case 'password':
                case 'select':
                case 'file':
                    if( empty($obj->_attributes['name']) ){ continue; }
                    $fields[ $obj->_attributes['name'] ]='text';
                    break;
                
                case 'date':
                case 'hierselect':
                    //$obj->_locale['_name'];
                    $fields[ $obj->_name ]='datetime';
                    break;
                case 'group':

            }
        }
        
        pr($fields);
        pr($elements);
        die;
        
        //$fields = $form->_elementIndex;
        unset( $fields[''], $fields['MAX_FILE_SIZE'] );
        
        foreach( $fields as $k=>$f ){
            if( stristr($k, 'qf_') !== false ){
                unset( $fields[$k] );
                continue;
            }
            $fields[$k]='text'; // 填入預設欄位型態text
        }
        
        return $fields;
    }
    
    /*****  Below Only For PEAR::QuickForm Renderer  *****/
    
    function getHtml( $form , $template='default' ){
        marktime('AppExecute', 'Start Renderring <span style="color:orange;">Form::getHTML( form_object, '.$template.' )</span>' );
        APP::load('pear', 'HTML/QuickForm/Renderer/Default');
        
        $method='_'.$template;
        if( method_exists( 'Form' , $method ) ){
            $renderer = self::$method();
        }
        
        $form->accept($renderer);
        
        $html=$renderer->toHtml();
        marktime('AppExecute', '<span style="color:orange;">Form::getHTML( form_object, '.$template.' )</span> Rendered' );
        return $html;
    }
    function getHtmlCode( $form , $template='default' ){
        $html='';
        $html.='<textarea style="width:700px;height:400px;">';
        $html.=htmlspecialchars( self::getHtml($form) );
        $html.='</textarea>';
        return $html;
    }
    function _privileges(){
        $renderer = new HTML_QuickForm_Renderer_Default();
        
        $headerTemplate='
                <div class="module">
                     <h2><span>{header}</span></h2>
                        
                         <div class="module-body">'."\n";
        $formTemplate = '
                     <form{attributes}>
                     {hidden}
                     {content}
                         </div> <!-- End .module-body -->
                     </form>
                </div>  <!-- End .module -->'."\n";
        $elementTemplate = '
                        <div style="float:left;height:30px;white-space:nowrap;">
                            <!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}
                            {element}
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                            &nbsp;&nbsp;&nbsp;&nbsp;
                        </div>'."\n";
        $groupTemplate = '
                        <fieldset>
                            <legend><!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}</legend>
                            <ul>
                                <li>{element}</li>
                            </ul>
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                        </fieldset>'."\n";
        $requiredNoteTemplate = "{requiredNote}";
        
        $renderer->setElementTemplate($elementTemplate);
        $renderer->setElementTemplate($groupTemplate, 'radio');
        $renderer->setElementTemplate($groupTemplate, 'checkbox');
        $renderer->setFormTemplate($formTemplate);
        $renderer->setHeaderTemplate($headerTemplate);
        $renderer->setRequiredNoteTemplate($requiredNoteTemplate);
        
        return $renderer;
    }
    function _search(){
        $renderer = new HTML_QuickForm_Renderer_Default();
        
        $headerTemplate=''."\n";
        $formTemplate = '
                     <form{attributes}>
                     {hidden}
                     {content}
                     </form>
                 <!-- End .module -->'."\n";
        $elementTemplate = '
                        <div style="float:left;height:30px;white-space:nowrap;">
                            <!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}
                            {element}
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                            &nbsp;&nbsp;&nbsp;&nbsp;
                        </div>'."\n";
        $groupTemplate = '
                        <fieldset>
                            <legend><!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}</legend>
                            <ul>
                                <li>{element}</li>
                            </ul>
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                        </fieldset>'."\n";
        $requiredNoteTemplate = "{requiredNote}";
        
        $renderer->setElementTemplate($elementTemplate);
        $renderer->setElementTemplate($groupTemplate, 'radio');
        $renderer->setElementTemplate($groupTemplate, 'checkbox');
        $renderer->setFormTemplate($formTemplate);
        $renderer->setHeaderTemplate($headerTemplate);
        $renderer->setRequiredNoteTemplate($requiredNoteTemplate);
        
        return $renderer;
    }
    function _rollcalls(){
        $renderer = new HTML_QuickForm_Renderer_Default();
        
        $headerTemplate='
                    <div class="module">
                    <h2><span>{header}</span></h2>
                    
                        <div class="module-body">'."\n";
        $formTemplate = '
                    <form{attributes}>
                    {hidden}
                    {content}
                        </div> <!-- End .module-body -->
                    </div>  <!-- End .module -->
                </form>
                '."\n";
        $elementTemplate = '
                            <!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {element}
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                        '."\n";
        $groupTemplate = '
                        <fieldset>
                            <legend><!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}</legend>
                            <ul>
                                <li> {element} </li>
                            </ul>
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                        </fieldset>'."\n";
        $requiredNoteTemplate = "{requiredNote}";
        
        $renderer->setElementTemplate($elementTemplate);
        $renderer->setElementTemplate($groupTemplate, 'radio');
        $renderer->setElementTemplate($groupTemplate, 'checkbox');
        //$renderer->setGroupTemplate('{content}', '');
        //$renderer->setGroupElementTemplate(' {label} {element}', '');
        $renderer->setFormTemplate($formTemplate);
        $renderer->setHeaderTemplate($headerTemplate);
        $renderer->setRequiredNoteTemplate($requiredNoteTemplate);
        
        return $renderer;
    }
    function _default(){
        $renderer = new HTML_QuickForm_Renderer_Default();
        
        $headerTemplate='
                <div class="module">
                    <h2><span>{header}</span></h2>
                        
                        <div class="module-table-body">
                        <table>'."\n";
        $formTemplate = '
                    <form{attributes}>
                    {hidden}
                    {content}
                        </table>
                        </div> <!-- End .module-body -->
                    </form>
                </div>  <!-- End .module -->'."\n";
        $elementTemplate = '
                        <tr style="border-bottom:1px solid #ccc;border-top:1px solid #ccc;">
                            <td style="vertical-align:top;">
                            <!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}
                            </td>
                            <td>
                            {element}
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                            </td>
                        </tr>'."\n";
        $groupTemplate = '
                        <fieldset>
                            <legend><!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
                            {label}</legend>
                            <ul>
                                <li> {element} </li>
                            </ul>
                            <!-- BEGIN error --><span class="notification-input ni-error">{error}</span><!-- END error -->
                        </fieldset>'."\n";
        $requiredNoteTemplate = " {requiredNote} ";
        
        $renderer->setElementTemplate($elementTemplate);
        $renderer->setElementTemplate($groupTemplate, 'radio');
        $renderer->setElementTemplate($groupTemplate, 'checkbox');
        //$renderer->setGroupTemplate('{content}', '');
        //$renderer->setGroupElementTemplate(' {label} {element}', '');
        $renderer->setFormTemplate($formTemplate);
        $renderer->setHeaderTemplate($headerTemplate);
        $renderer->setRequiredNoteTemplate($requiredNoteTemplate);
        
        return $renderer;
    }
}

?>