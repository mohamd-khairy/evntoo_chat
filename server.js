var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http, { cors: { origin: "*" } });
var users = [];
var groups = [];

io.on('connection', function (socket) {

    console.log("new client connected");

    socket.on('message', function (res, receiver_id, group_id) {


        // console.log('users', users);
        // console.log('receiver_id', receiver_id);
        // console.log('receiver_socket_id', users[receiver_id]);
        // console.log('groups', groups);
        // console.log('group_id', groups[group_id]);

        /*** this code for send messsage to the specific chat */
        socket.broadcast.to(groups[group_id]).emit('message', res);

        /*** this code for send messsage to the user in all chats general */
        // socket.broadcast.to(users[receiver_id]).emit("message", res);

    });


    socket.on('joinGroup', function (group_id) {
        groups[group_id] = group_id;
        socket.join(group_id);
        // console.log(groups);
    });

    socket.on("user_connected", function (user_id) {
        users[user_id] = socket.id;
        io.emit('updateUserStatus', users);
        console.log("user connected " + user_id);
    });

    socket.on('disconnect', function () {
        var i = users.indexOf(socket.id);
        users.splice(i, 1, 0);
        io.emit('updateUserStatus', users);
        // console.log(users);
    });

});

http.listen(9090, function () {

    console.log('socket.io server listen at 9090');

});

