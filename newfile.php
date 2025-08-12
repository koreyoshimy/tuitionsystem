//Write a function determine it is prime number
<?php 
function primeNumber($number){
    if ($number <= 1)
      return false;
    else if ($number == 2)
      return true;
    else if ($number %2 == 0)
      return false;
    $sqrt = sqrt($number);
    for($i=3; $i<= $sqrt; $i+=2)
    {
        if($number % $i == 0){
            return false;
        } 
    }
    
}
$testNumber = 25;
$result = primeNumber($testNumber);

if($result){
    echo "$testNumber is a prime number.";
}else{
    echo "$testNumber is not a prime number.";
}
?>
