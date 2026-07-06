// document.addEventListener("DOMContentLoaded", function () {
//     let steps = document.querySelectorAll(".ur-step");
//     let nextButtons = document.querySelectorAll(".ur-next-step");
//     let prevButtons = document.querySelectorAll(".ur-prev-step");
//     let currentStep = 0;

//     function showStep(index) {
//         steps.forEach((step, i) => {
//             step.style.display = i === index ? "block" : "none";
//         });
//     }

//     nextButtons.forEach((button, i) => {
//         button.addEventListener("click", function () {
//             if (currentStep < steps.length - 1) {
//                 currentStep++;
//                 showStep(currentStep);
//             }
//         });
//     });

//     prevButtons.forEach((button) => {
//         button.addEventListener("click", function () {
//             if (currentStep > 0) {
//                 currentStep--;
//                 showStep(currentStep);
//             }
//         });
//     });

//     showStep(currentStep);
// });
