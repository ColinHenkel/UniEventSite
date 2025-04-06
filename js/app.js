const urlBase = "http://www.colin201.xyz/LAMPAPI"
const extension = "php"

var userId = 0;
var holdUserID;
let firstName = "";
let lastName = "";
let email = "";
let role = "";

function createAccount() {
    firstName = document.getElementById("FirstName").value;
    lastName = document.getElementById("LastName").value;
    email = document.getElementById("email").value;
    role = document.getElementById("role").value.toLowerCase();
    const login = document.getElementById("createUsername").value;
    const password = document.getElementById("createPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (login == "" || password == "" || confirmPassword == "") {
        document.getElementById("loginResult").innerHTML = "Please fill in all fields.";
    }
    if (password !== confirmPassword) {
        document.getElementById("loginResult").innerHTML = "Passwords do not match.";
    }
    else {
        if (login && password && confirmPassword && firstName && lastName && email && role) {
            let tmp = {
                firstName: firstName,
                lastName: lastName,
                email: email,
                login: login,
                password: password,
                role: role
            };
            let jsonPayload = JSON.stringify(tmp);
            let url = urlBase + '/CreateAccount.' + extension;
            let xhr = new XMLHttpRequest();
            xhr.open("POST", url, true);
            xhr.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
            try {
                xhr.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        let jsonObject = JSON.parse(xhr.responseText);
                        if (jsonObject.error) {
                            document.getElementById("loginResult").innerHTML = jsonObject.error;
                        } else {
                            userId = jsonObject.userId;
                            if (userId < 1) {
                                document.getElementById("loginResult").innerHTML = "Account creation failed.";
                                return;
                            }
                        }
                    }
                };
                xhr.send(jsonPayload);
            } catch (err) {
                document.getElementById("loginResult").innerHTML = err.message;
            }
            document.getElementById("loginResult").innerHTML = "Account created successfully!";
            document.getElementById("loginRedirect").style.display = "inline";
        }
    }
    document.getElementById("loginResult").innerHTML = "";
}

function doLogin() {
    const login = document.getElementById("loginUsername").value;
    const password = document.getElementById("loginPassword").value;

    if (login == "" || password == "") {
        document.getElementById("loginResult").innerHTML = "Please fill in all fields.";
    } else {
        let tmp = {
            login: login,
            password: password
        };
        let jsonPayload = JSON.stringify(tmp);
        let url = urlBase + '/Login.' + extension;
        let xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
        try {
            xhr.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    let jsonObject = JSON.parse(xhr.responseText);
                    if (jsonObject.error) {
                        document.getElementById("loginResult").innerHTML = jsonObject.error;
                    } else {
                        userId = jsonObject.userId;
                        if (userId < 1) {
                            document.getElementById("loginResult").innerHTML = "User/Password combination incorrect.";
                            return;
                        }
                        let role = jsonObject.role;
                        
                        if (role === "superadmin") {
                            window.location.href = "superadminDashboard.html";
                        } else if (role === "admin") {
                            window.location.href = "adminDashboard.html";
                        } else {
                            window.location.href = "studentDashboard.html";
                        }
                    }
                }
            };
            xhr.send(jsonPayload);
        } catch (err) {
            document.getElementById("loginResult").innerHTML = err.message;
        }
    }
}