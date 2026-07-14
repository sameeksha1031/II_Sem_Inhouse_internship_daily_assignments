function calculate(){
        const num1 = Number(document.getElementById("number1").value)
        const num2 = Number(document.getElementById("number2").value)

        const operator = document.getElementById("operator").value

        let result = 0;
        switch(operator){
            case '+':
                result = num1 + num2
                break;
            case '-':
                result = num1 - num2
                break;
            case '/':
                if(num2 == 0){
                    result = "Cannot divde by 0"
                }
                else{
                    result = num1 / num2
                }
                break;
            case '*':
                result = num1 * num2
                break;
            
        }
         const res = document.getElementById("result");
         res.innerHTML = result
       }