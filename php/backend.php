<?php 

/**
  * Header php settings
  */

header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
define('LF', "\n");
define('BR','<br />'.LF);

/*Definition of class Transaction to represent a transaction with transaction details
 such as date, customer number, reference, amount and transaction code.
*/
class Transaction 
{
    //Properties for the class
    public $dateVar;
    public $transactionCode;
    public $customerNumber;
    public $reference;
    public $amount;
    private $transactionType;
    private $valid;

    //Methods for the class

    //Constructor
    public function __construct($date, $code, $number, $ref, $amt)
    {
        try {
            //Conversion of string to datetime
            $date = date_create_from_format('Y-m-d h:iA',$date);
            $this->dateVar = $date;
            $this->transactionCode = $code;
            $this->customerNumber = $number;
            $this->reference = $ref;
            //Conversion of amount from cents to currency
            $this->amount = $amt/100;
            //Checking type of transaction either credit or debit
            if ($this->amount >= 0) {
                $this->transactionType = "credit";
            } else {
                $this->transactionType = "debit";
            }
            //Checking if the transaction code is valid or invalid
            if (VerifyKey($code)) {
                $this->valid = "Yes";
            } else {
                $this->valid = "No";
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    //Setter methods for the class
    public function setDate($date) 
    {
        $date = date_create_from_format('Y-m-d h:iA',$date);
        $this->dateVar = $date;
    }
    
    public function setTransactionCode($code) 
    {
        $this->transactionCode = $code;
    }
    
    public function setCustomerNumber($number) 
    {
        $this->customerNumber = $number;
    }
    
    public function setReference($ref) 
    {
        $this->reference = $ref;
    }
    
    public function setAmount($amt) 
    {
        $this->amount = $amt/100;
    }

    //Getter methods for the class
    public function getTransactionDate() 
    {
        return $this->dateVar;
    }
    
    public function getTransactionCode() 
    {
        return $this->transactionCode;
    }
    public function getCustomerNumber() 
    {
        return $this->customerNumber;
    }
    public function getReference() 
    {
        return $this->reference;
    }
    public function getAmount() 
    {
        return $this->amount;
    }

    public function getTransactionType() 
    {
        return $this->transactionType;
    }

    public function getValidity() 
    {
        return $this->valid;
    }

    //Destructor method for the class
    public function __destruct() 
    {
        
    }
}

//Decleration of the valid code for checksum calculation
$validChars = array('2','3','4','5','6','7','8','9','A','B','C','D','E',
'F','G','H','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z');

/* Function to verify the validity of the code by comparing its length and if the 
last key matches the generated checksum code */
function VerifyKey($transactionCode) {
    //Length check for the code less than 10 then invalid
    if (strlen($transactionCode) != 10) {
        return false;
    } else {
        //Generate the checksum code and compare with the last digit
        $checkDigit = GenerateCheckCharacter(substr(strtoupper($transactionCode), 0, 9));
        return $transactionCode[9]==$checkDigit;
    }
}

// Function to generate the checksum code using Luhn mod n algorithm.
function GenerateCheckCharacter($input) {
    //Initial factor is considered to be 2
    $factor = 2;
    $sum = 0;
    global $validChars;
    $n = count($validChars);
    //Iterate from the end of the code towards the start of the string
    for ($i = strlen($input)-1; $i >= 0; $i--) {
        //Retrieve the position of the character in the array of acceptable characters
        $codePoint = array_search($input[$i], $validChars);
        $addend = $factor*$codePoint;
        //Change the value of factor based on previous value
        $factor = ($factor==2) ? 1:2; 
        $addend = floor(($addend/$n))+($addend % $n);
        $sum = $sum+$addend;
    }
    $remainder = $sum % $n;
    //Generate the remainder value after modulus by n as the array size is n
    $checkCodePoint = ($n - $remainder) % $n;
    //Return the character value in the position value of remainder
    return $validChars[$checkCodePoint];
}

/* Function to check the type of the transaction inputs and their validity,
    i.e., verify if the transaction matches the criteria and is correct or not. */
function conditionCheck($date, $code, $number, $ref, $amt) {
    try {
        //Check if the date is of the specified format
        if (is_string($date)==1 and date_create_from_format('Y-m-d h:iA',$date)) {
            //Check if the code and the reference are string
            if (is_string($code) and is_string($ref)) {
                if ((gettype($number) == "string") and (gettype($amt) == "string")) {
                    //Check if the number and the amount are of integer type
                    $number = intval($number);
                    $amt = intval($amt);
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } catch(Exception $e) {
        echo $e->getMessage();
        return false;
    }
}

/* The main section of the code, this section is called when the file is 
    uploaded in the frontend and passed to this php file. */
// Check if the file uploaded is empty
if (!empty($_FILES['filename']['name']))
{
    try {
        // Check if the file uploaded can be read
        if (($handle = fopen($_FILES['filename']['tmp_name'], "r"))!=false) {
            //Read the first line of the file which are the headers
            $headers = fgetcsv($handle, 1000, ",");
            //Array to store the valid transaction objects
            $transactions = array();
            $index = 0;
            
            //Iterate through the contents of the file until the end of file
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                //Check if the row contents or data of each transaction is valid
                if (conditionCheck($data[0], $data[1], $data[2], $data[3], $data[4])) {
                    // Create a new transaction object if the contents are valid
                    $transactions[$index] = new Transaction($data[0], $data[1], 
                    $data[2], $data[3], $data[4]);
                    $index++;
                }
            }
            
            if ($index!=0) {
                // Sort the transaction object array based on date of transaction
                usort($transactions,function($a, $b) {
                    //Compare the date of two transaction
                    if ($a->dateVar == $b->dateVar) { return 0; }
                    return ($a->dateVar < $b->dateVar) ? -1:1; 
                });
                
                //Create the table for transaction to be displayed in the frontend
                echo "<table id='transaction_table'>";
                echo "<tr><th>Date</th><th>Transaction Code</th><th>Valid Transaction?
                </th><th>Customer Number</th><th>Reference</th><th>Amount</th></tr>";
                
                foreach ($transactions as $a) {
                    echo "<tr><td>".($a->dateVar)->format('d/m/Y h:iA')."</td><td>".
                    $a->transactionCode."</td><td>".$a->getValidity()."</td><td>".
                    $a->customerNumber."</td><td>".$a->reference."</td><td class='".
                    $a->getTransactionType()."'>".$a->amount."</td></tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p> No contents present in the file. </p>";
            }
            //Close the file
            fclose($handle);
        }
    } catch(Exception $e) {
        echo "<p>".$e->getMessage()."</p>";
    }   
} else {
    echo "<h2 class='debit'>Please upload a file.</h2>";
}
