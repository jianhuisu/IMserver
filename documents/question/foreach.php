<?php

    $i = 1;
    $j = &$i;  // $i variable change , from long to IS_REFERENCE
    $j = 2;

    echo "i value : $i ; j value : $j \n"; // 2 , 2
    xdebug_debug_zval('i');
    xdebug_debug_zval('j');

    unset($j);
    //echo "i value : $i ; j value : $j \n"; // 2 , NULL


   // unset($i);
   // echo "i value : $i ; j value : $j \n"; // NULL , 2

    $a=[1,2,3];
    foreach($a as &$v){
        var_dump($v);
    }

    // $v = &$a[0]
    // $v = &$a[1]
    // $v = &$a[2]

    echo "------\n";
    foreach($a as $v){
        var_dump($v);
    }

    // $a[2] = $a[0]  : $a = [1,2,1]
    // $a[2] = $a[1]  : $a = [1,2,2]
    // $a[2] = $a[2]  : $a = [1,2,2]

    print_r($a);

// 输出结果是 1 2 2
// 在 PHP 中，foreach 结束后，循环中的索引值（index）及內容（value）並不会被重置。
// 所以在第一个foreach循环中的 最后一个 $v 还引用指向最后一个元素，当紧接着再次foreach循环时，就会把最后个元素的值修改掉了。
// 解决的办法是，在第一个循环完毕之后，用unset($v); 销毁 $v 从而间接解除$v与数组$a元素之间的引用,
// 这样 在下面重新 foreach时 定义的$v 就是一个新的变量 与$a 不存在引用关系了.


//current():取得目前指针位置的内容资料。
//key():读取目前指针所指向资料的索引值（键值）。
//next():将数组中的内部指针移动到下一个单元。
//prev():将数组的内部指针倒回一位。
//end():将数组的内部指针指向最后一个元素。
//reset():将目前指针无条件移至第一个索引位置。
// C 库函数 void rewind(FILE *stream) 设置文件位置为给定流 stream 的文件的开头。