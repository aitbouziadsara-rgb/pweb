document.getElementById("ButtonLogin").addEventListener("click", function() {
    const email = document.getElementById("gmailLogin").value;

if (!email.includes("@")) {
    alert("Please enter a valid email address.");
} else {
    window.location.href = "profilUser.html";
}
    
});

