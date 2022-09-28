const moment = require("moment");
const cors = require("cors");
const express = require("express");
const app = express();
const server = require("http").Server(app);
const axios = require("axios");
const io = require("socket.io")(server, {
    cors: "*",
});

// const { addUser, getUser } = require("./users");

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

server.listen(process.env.PORT || 5000, () =>
    console.log(`Server is ready on port 5000`)
);

app.get("/", (req, res) => {
    res.send("hello world!");
});

const main = async () => {
    // const endpoint = "http://sahl-app.com";
    const endpoint = "https://admin.evntoo.website"
    // const endpoint = "http://127.0.0.1:8000"

    // const response = await axios(`${endpoint}/api/chat/users`)
    // const users = await response.data.data
    // const privateUsers = users
    // users.filter(user => user.city_id)

    io.on("connection", (socket) => {
        console.log(`new user connected!`);

        socket.on("join", ({ userId, room, username }) => {
            console.log("start db");
            console.log({ userId, room, username });

            // const db_user = users.find(
            //   (user) => user.id === userId && user.city_id === parseInt(room)
            // );
            // console.log(db_user);

            // if (!db_user) {
            //   io.emit("unjoin", { status: 401 });
            //   return;
            // }

            // console.log(`new user just joined!`);

            // const { user, error } = addUser({
            //   id: socket.id,
            //   username: db_user.username,
            //   room: db_user.city_id,
            // });

            const { user, error } = addUser({
                id: socket.id,
                username: username,
                room: room,
            });

            console.log('hhhh', user);

            if (user) {
                io.emit("unjoin", { status: 401 });
                return;
            }

            socket.emit("chat:message", {
                username: "admin",
                text: `Hi ${user.username}, Welcome to the chat!`,
            });

            socket.broadcast.to(user.room).emit("message", {
                username: "admin",
                text: `${user.username} has joined the chat!`,
            });

            socket.join(user.room);
        });

        socket.on("join:private", ({ userId, room, username }) => {
            console.log("private start db");
            console.log({ userId, room, username });

            // const db_user = privateUsers.find(
            //   (user) => user.id === userId
            // );
            // if (!db_user) {
            //   io.emit("unjoin", { status: 401 });
            //   return;
            // }

            // console.log(`new user just joined!`);

            // const { user, error } = addUser({
            //   id: socket.id,
            //   username: db_user.username,
            //   room: room,
            // });

            const { user, error } = addUser({
                id: socket.id,
                username: username,
                room: room,
            });

            if (user) {
                io.emit("unjoin", { status: 401 });
                return;
            }

            socket.emit("chat:message", {
                username: "admin",
                text: `Hi ${user.username}, Welcome to the chat!`,
            });

            socket.broadcast.to(user.room).emit("message", {
                username: "admin",
                text: `${user.username} has joined the chat!`,
            });

            socket.join(user.room);
        });

        socket.on("chat:send", async ({ userId, username, type, text, url, lat, long }) => {
            console.log({ userId, username, type, text, url, lat, long });

            console.log("sending new message...");
            console.log(socket);

            const user = getUser(socket.id);
            console.log(user);

            if (!user) return { message: "not authroized to enter this room" };
            console.log(user);
            console.log({ userId, username, type, text, url, lat, long });

            axios
                .post(`${endpoint}/api/chat/add-message`, {
                    user_id: userId,
                    username,
                    type,
                    text,
                    url,
                    lat,
                    long,
                    city_id: user.room,
                })
                .then((res) => {
                    console.log("here");
                    console.log(res.data);

                    const data = res.data.data || null;
                    console.log(data);
                    io.to(data.city_id).emit("chat:message", data);
                    socket.broadcast.to(data.city_id).emit("chat:message", data);
                })
                .catch((err) => console.log(err));
        }
        );

        socket.on("currentLocation", ({ user_id, lat, long }) => {
            console.log("####################################3");
            console.log({ user_id, lat, long })
            axios
                .post(`${endpoint}/api/current-location`, { user_id, lat, long })
                .then((res) => {
                    console.log("new location has been set!");
                    console.log(res.data || res);
                    io.emit("upadatedLocation", res.data.data);
                });
        });
    });
};

main();