<?php
namespace HeritagePress\Models\Traits;

trait HasValidation {
    /**
     * The validation rules for the model
     */
    protected $rules = [];
    
    /**
     * The validation error messages
     */
    protected $errors = [];
    
    /**
     * Run validation on the model's attributes
     */
    public function validate($data = null) {
        $data = $data ?: $this->attributes;
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($fieldRules as $rule) {
                $this->validateField($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate a single field
     */
    protected function validateField($field, $value, $rule) {
        if (is_string($rule)) {
            if (strpos($rule, ':') !== false) {
                list($rule, $parameter) = explode(':', $rule, 2);
            } else {
                $parameter = null;
            }
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    $this->addError($field, 'The :field is required.');
                }
                break;
                
            case 'string':
                if (!is_null($value) && !is_string($value)) {
                    $this->addError($field, 'The :field must be a string.');
                }
                break;
                
            case 'numeric':
                if (!is_null($value) && !is_numeric($value)) {
                    $this->addError($field, 'The :field must be numeric.');
                }
                break;
                
            case 'date':
                if (!is_null($value) && !strtotime($value)) {
                    $this->addError($field, 'The :field must be a valid date.');
                }
                break;
                
            case 'email':
                if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'The :field must be a valid email address.');
                }
                break;
                
            case 'min':
                if (!is_null($value)) {
                    if (is_numeric($value) && $value < $parameter) {
                        $this->addError($field, "The :field must be at least $parameter.");
                    } elseif (is_string($value) && strlen($value) < $parameter) {
                        $this->addError($field, "The :field must be at least $parameter characters.");
                    }
                }
                break;
                
            case 'max':
                if (!is_null($value)) {
                    if (is_numeric($value) && $value > $parameter) {
                        $this->addError($field, "The :field may not be greater than $parameter.");
                    } elseif (is_string($value) && strlen($value) > $parameter) {
                        $this->addError($field, "The :field may not be greater than $parameter characters.");
                    }
                }
                break;
        }
    }
    
    /**
     * Add a validation error
     */
    protected function addError($field, $message) {
        $this->errors[$field][] = str_replace(':field', $field, $message);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if the model has validation errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
