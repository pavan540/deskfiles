function loadContent(page) {
    let contentArea = document.getElementById('content-area');
    switch (page) {
        case 'home':
            contentArea.innerHTML = '<h2>Home</h2><p>Welcome to the home page!</p>';
            break;
        case 'Check Attendance':
            contentArea.innerHTML = '<h2>Check Attendance</h2><p>Check Attendance</p>';
            break;
        case 'Course Enrollment':
            contentArea.innerHTML = '<h2>Course Enrollment</h2><p>Course Enrollment</p>';
            break;
        case 'CA Marks':
            contentArea.innerHTML = '<h2>CA Marks</h2><p>Continuous Assessment Marks</p>';
            break;
        case 'SE Marks Report':
            contentArea.innerHTML = '<h2>SE Marks Report</h2><p>Semester End Marks Report</p>';
            break;
        case 'Logout':
            contentArea.innerHTML = '<h2>Logout</h2><p>You have been logged out!</p>';
            break;
        default:
            contentArea.innerHTML = '<p>Select an option from the menu to see the content here.</p>';
    }
}
