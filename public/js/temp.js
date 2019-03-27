var newData = data.map(function(item,key){
  
    // transform item with new keys
    var newItem={
      id_item: item.field_4_raw,
      address: item.field_8_raw,
      check_in: item.field_11,
      check_out: item.field_12,
      telephone: item.field_9_raw.full
    };
   
    return newItem;
  
  });