<?php

namespace BenAllfree\LaravelEasyAttachments;
trait Attachable
{
  public function hasSetMutator($key)
  {
    preg_match('/(.*)_(image|file)_path$/', $key, $matches);
    if(count($matches)>0)
    {
      return true;
    }
    return parent::hasSetMutator($key);
  }
  
  public function setAttribute($key, $value)
  {
    dd($key);
    preg_match('/(.*)_(image|file)(_la)?$/', $key, $matches);
    if(count($matches)>0)
    {
      list($match_data, $field_prefix, $field_type) = $matches;
      $la_mode = count($matches)==4;
      $field_name = "{$field_prefix}_{$field_type}_id";
      if(!$value)
      {
        return parent::setAttribute($field_name, null);
      }
      if(!preg_match('/^https?:/', $value)) // If this is a local path
      {
        $try = [
          $value,
          config('laravel-stapler.easy-attachments.la_path')."/{$value}",
          storage_path($value),
          app_path($value),
        ];
        foreach($try as $file_path)
        {
          if(file_exists($file_path) && is_file($file_path))
          {
            $value = $file_path;
            break;
          }
        }
        if($value != $file_path)
        {
          if($la_mode)
          {
            return $this->getAttribute($field_name);
          } else {
            throw new \Exception("File path to save {$value} not found.");
          }
        }
      }
      switch($field_type)
      {
        case 'image':
          $Image = config('easy-attachments.image_class');
          $i = $Image::fromUrl($value);
          break;
        case 'file':
          $Attachment = config('easy-attachments.attachment_class');
          $i = $Attachment::fromUrl($value);
          $i = Attachment::fromUrl($value);
          break;
        default:
          throw new \Exception("Unrecognized attachment type {$field_type}");
      }
      if($la_mode)
      {
        copy($i->path('admin'), $value);
      }
      return parent::setAttribute($field_name, $i->id);
    }
    return parent::setAttribute($key, $value);
  }
  
  public function hasGetMutator($key)
  {
    preg_match('/(.*)_(?:image|file)(?:_la)?$/', $key, $matches);
    if(count($matches)>0)
    {
      return true;
    }
    preg_match('/(.*)(?:Image|File)$/', $key, $matches);
    if(count($matches)>0)
    {
      return true;
    }
    return parent::hasGetMutator($key);
  }
  
  public function mutateAttribute($key, $value)
  {
    preg_match('/(.*)_(image|file)(_la)?$/', $key, $matches);
    if(count($matches)>0)
    {
      list($match_data, $field_name_prefix, $field_type) = $matches;
      $la_mode = count($matches)==4;
      $field_name = "{$field_name_prefix}_{$field_type}_id";
      if(!$this->$field_name) return null;
      switch($field_type)
      {
        case 'image':
          $Image = config('easy-attachments.image_class');
          $obj = $Image::find($this->$field_name);
          break;
        case 'file':
          $Attachment = config('easy-attachments.attachment_class');
          $obj = Attachment::find($this->$field_name);
          break;
        default:
          throw new \Exception("Unrecognized attachment type {$field_type}");
      }
      if(!$obj) return null;
      if($la_mode)
      {
        // Recover image if missing from Laravel Admin
        $la_fpath = config('laravel-stapler.easy-attachments.la_path')."/{$obj->att_file_name}";
        if(!file_exists($la_fpath))
        {
          copy($obj->path('admin'), $la_fpath);
        }
        
        return $obj->att_file_name;
      }
      return $obj;
    }
    preg_match('/(.*)(Image|File)$/', $key, $matches);
    if(count($matches)==0) return parent::mutateAttribute($key, $value);
    return $this->getRelationValue($key);
  }
  
  public function __call($name, $args)
  {
    preg_match('/(.*)(Image|File)$/', $name, $matches);
    if(count($matches)==0) return parent::__call($name, $args);
    list($match_data, $field_name_prefix, $field_type) = $matches;
    $field_name_prefix = snake_case($field_name_prefix);
    $field_type = strtolower($field_type);
    $field_name = "{$field_name_prefix}_{$field_type}_id";
    switch($field_type)
    {
      case 'image':
        $Image = config('easy-attachments.image_class');
        return $this->belongsTo($Image, $field_name);
      case 'file':
        $Attachment = config('easy-attachments.attachment_class');
        return $this->belongsTo($Attachment, $field_name);
      default:
        throw new \Exception("Unrecognized attachment type {$field_type}");
    }
  }
  
  public function getRelationValue($key)
  {
      if ($this->relationLoaded($key)) {
        return $this->relations[$key];
      }

      if (method_exists($this, $key) || preg_match('/(.*)(Image|File)$/', $key)) {
        return $this->getRelationshipFromMethod($key);
      }
  }
}