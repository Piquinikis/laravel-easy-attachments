<?php
namespace BenAllfree\LaravelEasyAttachments;

use Codesleeve\Stapler\ORM\StaplerableInterface;
use Codesleeve\Stapler\ORM\EloquentTrait;

class Attachment  extends \Eloquent implements StaplerableInterface 
{
  use EloquentTrait;

  public static function fromUrl($url, $force_fetch=false)
  {
    if(!$force_fetch)
    {
      $i = Attachment::whereOriginalFileName($url)->first();
      if($i) return $i;
    }
    $i = new Attachment();
    $i->original_file_name = $url;
    $i->att = $url;
    $i->save();
    return $i;
  }
  
  function getTable()
  {
    return config('easy-attachments.table_name');
  }
  
  public function __construct(array $attributes = array()) {
    $this->hasAttachedFile('att');

    parent::__construct($attributes);
  }
  
  function url()
  {
    return $this->att->url();
  }
  
  function path()
  {
    return $this->att->path();
  }  
}