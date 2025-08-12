const express = require('express');
const bodyParser = require('body-parser');

const app = express();
const port = 3000;

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// ... (QR code scanning and data processing logic)

app.post('/attendance', (req, res) => {
    const attendanceData = req.body;
    // Process attendance data (e.g., store in a database)
    console.log('Received attendance data:', attendanceData);
    res.send('Attendance recorded successfully');
});

app.listen(port, () => {
    console.log(`Server listening on port ${port}`);
});