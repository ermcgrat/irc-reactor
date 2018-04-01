// Todo
// Radio tcl import (+ shortcuts > play/vote/search)
// periodic dedication check
// Modularize components

import * as mysql from 'mysql';
import * as rp from 'request-promise';
import { Promise } from 'bluebird';
const jerk = require('jerk');
const imdb = require('imdb-node-api');
const GoogleSearch = require('google-search');

const ircConfig = {
    server: 'east.irc-reactor.com',
    nick: 'Jerk',
    channels: ['#sov']
};

const mysqlConfig: mysql.ConnectionConfig = {
    host: 'east.irc-reactor.com',
    user: '',
    password: '',
    database: 'radio'
};

const googleSearch = new GoogleSearch({
    key: 'AIzaSyDeAt7u8gbx5LRGnwI4d6ncmu_MkKPdEoE',
    cx: '011866386975486027187:85pfee1jvwu'
});

const enableRadioRequests = true;
const radioRelay = 'http://radio.irc-reactor.com:8018/listen.pls';
// how many days back the top5 requests should search. 0 for no limit.
const requestDays = 30;

jerk(j => {

    j.watch_for('!hello', function (message) {
        message.say('Hello!' + JSON.stringify(message));

        rp.get('http://httpbin.org/ip').then(val => {
            message.say(JSON.stringify(JSON.parse(val)));
        });
    });

    // colors demo
    j.watch_for(/^!colors$/, message => {
        message.say('Do you like colors?');
        let line = '';
        let count = 0;
        let code;

        for (let i = 0; i <= 99; i++) {
            code = i < 10 ? '0' + i : i;
            count++;
            line += '\x03' + code + code + ' ';
            if (count >= 20) {
                message.say(line);
                count = 0;
                line = '';
            }
        }
    });

    // // google search
    j.watch_for(/^!google (.+)$/, message => {
        googleSearch.build({
            q: message.match_data[1],
            num: 1
        }, (err, response) => {
            if (err) {
                message.say('\x0304I have failed you. ' + err);
            } else if (response.error) {
                message.say('\x0304I have failed you. ' + response.error.errors[0].reason);
            } else {
                const searchTime = response.searchInformation.formattedSearchTime;
                const totalResults = response.searchInformation.formattedTotalResults;
                const title = response.items[0].title;
                const link = response.items[0].link.replace(' ', '');
                const summary = response.items[0].snippet.replace(/\n/g, '');
                message.say('\x0304Found ' + totalResults + ' results \x0307in ' + searchTime + ' seconds.');
                message.say('\x0312Top result: ' + title);
                message.say('\x0303Summary: ' + summary);
                message.say('\x0310Read more: ' + link);
            }
        });
    });

    // google fight
    j.watch_for(/^!fight (.+)$/, message => {
        const full = message.match_data[1];
        const vsIndex = full.indexOf(' vs ');
        const contestant1 = full.substr(0, vsIndex);
        const contestant2 = full.substr(vsIndex + 4);

        if (vsIndex === -1 || !contestant1 || !contestant2 || contestant1 === contestant2) {
            message.say('Fight syntax is: <something> vs <something else>');
        } else if (contestant1.length <= 2 || contestant2.length <= 2) {
            message.say('Your contestant is too small to fight!');
        } else {
            // commence the fight
            const c1Promise = new Promise((resolve, reject) => {
                googleSearch.build({
                    q: contestant1,
                    num: 1
                }, (err, response) => {
                    if (err) {
                        reject(new Error(err));
                    } else if (response.error) {
                        reject(response.error.errors[0].reason);
                    } else {
                        resolve({
                            totalResults: response.searchInformation.totalResults,
                            formattedTotalResults: response.searchInformation.formattedTotalResults,
                        });
                    }
                });
            });

            const c2Promise = new Promise((resolve, reject) => {
                googleSearch.build({
                    q: contestant2,
                    num: 1
                }, (err, response) => {
                    if (err) {
                        reject(new Error(err));
                    } else if (response.error) {
                        reject(response.error.errors[0].reason);
                    } else {
                        resolve({
                            totalResults: response.searchInformation.totalResults,
                            formattedTotalResults: response.searchInformation.formattedTotalResults,
                        });
                    }
                });
            });

            Promise.all([c1Promise, c2Promise]).then((results: any[]) => {
                if (parseInt(results[0].totalResults, 10) === parseInt(results[1].totalResults, 10)) {
                    message.say('\x0312' + contestant1 + ' \x03and \x0307' + contestant2 + ' \x0345TIE \x03with \x0345' +
                        results[0].formattedTotalResults + '\x03 total results!');
                } else if (parseInt(results[0].totalResults, 10) > parseInt(results[1].totalResults, 10)) {
                    message.say('\x0312' + contestant1 + ' \x0303BEATS \x0307' + contestant2 + '\x03 with\x0312 ' +
                        results[0].formattedTotalResults + '\x03 to\x0307 ' + results[1].formattedTotalResults + '\x03 total results!');
                } else {
                    message.say('\x0312' + contestant1 + ' \x0304LOSES TO \x0307' + contestant2 + '\x03 with\x0312 ' +
                        results[0].formattedTotalResults + '\x03 to\x0307 ' + results[1].formattedTotalResults + '\x03 total results!');
                }
            }).catch(err => {
                message.say('\x03041I have failed you.\x0345 ' + err);
            });
        }
    });

    // imdb (movie search, does not search tv shows)
    j.watch_for(/^!imdb (.+)$/, message => {
        const searchTerm = message.match_data[1];
        imdb.search({
            keyword: searchTerm
        }, (err, data) => {
            if (err) {
                message.say('\x0304I have failed you. ' + err);
            } else {
                data = JSON.parse(data);
                if (data && data.movies) {
                    // we found something. Let's get details for the #1 result
                    const id = data.movies[0].imdbId;
                    const title = data.movies[0].name;
                    const year = data.movies[0].year;

                    imdb.getByImdbId(id, (err2, data2) => {
                        if (err2) {
                            message.say('\x0304I have failed you. ' + err2);
                        } else {
                            // we found details
                            data2 = JSON.parse(data2);
                            const rating = data2.movie.ratingValue;
                            const genres = data2.movie.genre.join(', ');
                            const summary = data2.movie.summaryText;
                            const actors = data2.movie.actors.splice(0, 3).map(actor => actor.name).join(', ');
                            message.say('\x0345[IMDB] \x0304Movie: ' + title + ' \x0310(' + year + ') \x0312Rating: ' + rating);
                            message.say('\x0303Summary: ' + summary);
                            message.say('\x0307Genres: ' + genres + ' \x0304Lead Actors: ' + actors);
                            message.say('\x0310Read more: http://www.imdb.com/title/' + id);
                        }
                    });
                } else {
                    message.say('I didn\'t find anything for: ' + searchTerm);
                }
            }
        });
    });

    // Arnold
    j.watch_for('what is best in life?', message => {
        message.say('To crush your enemies, to see them driven before you, and to hear the lamentations of their women!');
    });

    // Fortune
    j.watch_for(/^!8ball (.+)$/, message => {
        const rand = Math.floor(Math.random() * 100) + 1;
        if (rand <= 5) {
            message.say('\x0304No, fuck you');
        } else if (rand <= 10) {
            message.say('It is decidedly so');
        } else if (rand <= 15) {
            message.say('Without a doubt');
        } else if (rand <= 20) {
            message.say('Yes definitely');
        } else if (rand <= 25) {
            message.say('You may rely on it');
        } else if (rand <= 30) {
            message.say('As I see it, yes');
        } else if (rand <= 35) {
            message.say('Most likely');
        } else if (rand <= 40) {
            message.say('Outlook good');
        } else if (rand <= 45) {
            message.say('Yes');
        } else if (rand <= 50) {
            message.say('Signs point to yes');
        } else if (rand <= 55) {
            message.say('Reply hazy try again');
        } else if (rand <= 60) {
            message.say('Ask again later');
        } else if (rand <= 65) {
            message.say('Better not tell you now');
        } else if (rand <= 70) {
            message.say('Cannot predict now');
        } else if (rand <= 75) {
            message.say('Concentrate and ask again');
        } else if (rand <= 80) {
            message.say('Dont count on it');
        } else if (rand <= 85) {
            message.say('My reply is no');
        } else if (rand <= 90) {
            message.say('My sources say no');
        } else if (rand <= 95) {
            message.say('Outlook not so good');
        } else {
            message.say('Very doubtful');
        }
    });

    // Radio
    const handleMysqlError = (error: any, message: any): void => {
        message.say('Error: ' + JSON.stringify(error));
    };

    const msToTime = (duration: number): string => {
        let hours, minutes, seconds, display = '';
        seconds = Math.floor(duration / 1000);
        minutes = Math.floor(seconds / 60);
        seconds = seconds % 60;
        hours = Math.floor(minutes / 24);
        minutes = minutes % 60;
        hours = hours % 24;

        // always pad seconds
        seconds = seconds < 10 ? '0' + seconds : seconds;

        // pad minutes if there are hours
        if (hours > 0 && minutes < 10) {
            minutes = '0' + minutes;
        }

        // only display hours if there are any
        if (hours > 0) {
            display += hours + ':';
        }

        display += minutes + ':' + seconds;
        return display;
    };

    interface ISongRecord {
        songId: number;
        artist: string;
        title: string;
        duration: number;
        album?: string;
        rating?: number;
    }

    const songInfo = (song: ISongRecord): string => {
        let result = '';
        if (song.songId) {
            result += `\x0312${song.songId}: `;
        }
        result += `\x0303${song.artist} - ${song.title} \x0310(${msToTime(song.duration)})`;
        if (song.album) {
            result += `\x0307 (${song.album})`;
        }
        if (song.rating) {
            result += `\x0306 Rating: ${song.rating}`;
        }
        return result;
    };

    j.watch_for(/^!best5j$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT sl.ID, sl.artist, sl.title, sl.duration, sl.album, sl.id as songId, v.rating, v.cnt
            From songlist sl
                left join (select avg(score) as rating, count(ID) as cnt, songId From votez Group By songId) as v On sl.ID = v.songId
            Order By v.rating desc, v.cnt desc Limit 0,5`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (results.length) {
                message.msg(`\x0304Highest rated songs:`);
                for (let i = 0; i < results.length; i++) {
                    message.msg(`\x0304Voted for ${results[i].cnt} time(s): ` + songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    j.watch_for(/^!commandsj$/, message => {
        message.say('\x0312Radio commands: \x0303!help !play !search !vote !playing !next !prev !listen !listeners !stats !new !top5 !least5 !most5 !best5');
    });

    j.watch_for(/^!helpj$/, message => {
        message.msg('\x0303Radio command list:');
        if (enableRadioRequests) {
            message.msg('\x0312!play \x0307<song id>\x0F - \x0303Request a song to be played. Song ID is shown in \x0304!search, !new, !next, and !prev');
            message.msg('\x0312!search \x0307<query>\x0F - \x0303Searches songs by \x0304Song ID, Artist, Song Title, Album Name');
            message.msg('\x0303 -> Example \x0312!search symphony \x0303will output every song with artist, album, or song title containing \x0304symphony');
        }
        message.msg('\x0312!vote \x0307<1 - 5> \x0F- \x0303Vote for the current song on a scale of 1 to 5');
        message.msg('\x0312!playing\x0F - \x0303Displays the current song playing on the radio');
        message.msg('\x0312!next\x0F - \x0303Displays the two songs playing next on the radio');
        message.msg('\x0312!prev\x0F - \x0303Displays the two songs played previously');
        message.msg('\x0312!listen\x0F - \x0303How to listen to this radio station');
        message.msg('\x0312!listeners\x0F - \x0303How many people are listening to the radio');
        message.msg('\x0312!stats\x0F - \x0303Show the listener peak');
        message.msg('\x0312!new\x0F - \x0303Lists newly-added songs to this radio station');
        message.msg('\x0312!top5\x0F - \x0303Show the 5 most recently-requested songs ' + (requestDays ? ' in the last ' + requestDays + ' days' : ''));
        message.msg('\x0312!least5\x0F - \x0303Show the 5 least-played songs of all time');
        message.msg('\x0312!most5\x0F - \x0303Show the 5 most-played songs of all time');
        message.msg('\x0312!best5\x0F - \x0303Show the 5 highest-rated songs of all time');
    });

    j.watch_for(/^!least5j$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT sl.ID, sl.artist, sl.title, sl.duration, sl.album, sl.id as songId, v.rating, count_played
            From songlist sl
                left join (select avg(score) as rating, songId From votez Group By songId) as v On sl.ID = v.songId
            Order By count_played, date_played Limit 0,5`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (results.length) {
                message.msg(`\x0304Least played songs:`);
                for (let i = 0; i < results.length; i++) {
                    message.msg(`\x0304Played ${results[i].count_played} time(s): ` + songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    j.watch_for(/^!listenj$/, message => {
        message.say(`\x0312${radioRelay}`);
    });

    j.watch_for(/^!listenersj$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = 'SELECT listeners FROM historylist ORDER BY date_played DESC LIMIT 0, 1';
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else {
                message.say(`\x0304There are ${results[0].listeners} people currently listening to the radio.`);
            }
        });

        connection.end();
    });

    j.watch_for(/^!most5j$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT sl.ID, sl.artist, sl.title, sl.duration, sl.album, sl.id as songId, v.rating, count_played
            From songlist sl
                left join (select avg(score) as rating, songId From votez Group By songId) as v On sl.ID = v.songId
            Order By count_played desc, date_played desc Limit 0,5`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (results.length) {
                message.msg(`\x0304Most played songs:`);
                for (let i = 0; i < results.length; i++) {
                    message.msg(`\x0304Played ${results[i].count_played} time(s): ` + songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    j.watch_for(/^!newj$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT sl.ID, sl.artist, sl.title, sl.duration, sl.album, sl.id as songId, v.rating
            From songlist sl
                left join (select avg(score) as rating, songId From votez Group By songId) as v On sl.ID = v.songId
            Order By date_added desc Limit 0,5`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (results.length) {
                message.msg(`\x0304Most recently-added songs:`);
                for (let i = 0; i < results.length; i++) {
                    message.msg(songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    j.watch_for(/^!nextj$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT songlist.ID, songlist.artist, songlist.title, songlist.duration, songlist.album, songlist.id as songId, v.rating
            FROM queuelist, songlist
                left join (select avg(score) as rating, songId From votez Group By songId) as v On songlist.ID = v.songId
            WHERE (queuelist.songID = songlist.ID)  AND (songlist.songtype='S') AND (songlist.artist <> '')
            ORDER BY queuelist.sortID ASC LIMIT 0, 2`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (!results.length) {
                message.say('\x0304No upcoming requests at the moment.');
            } else {
                message.say('\x0304Coming next:');
                for (let i = 0; i < results.length; i++) {
                    message.say(songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    j.watch_for(/^!playingj$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `select sl.artist, sl.title, sl.duration, sl.album, sl.ID as songId, v.rating
            from songlist sl
                inner join historylist hl ON hl.songId = sl.ID
                left join (select avg(score) as rating, songId From votez Group By songId) as v On sl.ID = v.songId
            Where sl.songtype = 'S'
            ORDER BY hl.date_played DESC LIMIT 0, 1`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (results.length) {
                message.say('\x0304Currently playing: ' + songInfo(results[0]));
            }
        });

        connection.end();
    });

    j.watch_for(/^!prevj$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT songlist.ID, songlist.artist, songlist.title, songlist.duration, songlist.album, songlist.id as songId, v.rating
            FROM historylist,songlist
            left join (select avg(score) as rating, songId From votez Group By songId) as v On songlist.ID = v.songId
            WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S')
            ORDER BY historylist.date_played DESC LIMIT 1, 2`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (!results.length) {
                message.say('\x0304No songs played previously.');
            } else {
                message.say('\x0304Previously played:');
                for (let i = 0; i < results.length; i++) {
                    message.say(songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    j.watch_for(/^!searchj (.+)$/, message => {
        const search = message.match_data[1].replace(/'/g, '\'\'');
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = `SELECT artist, title, duration, ID as songId, album, v.rating
            FROM songlist
            left join (select avg(score) as rating, songId From votez Group By songId) as v On songlist.ID = v.songId
            WHERE (title like '%${search}%') OR (artist like '%${search}%') OR (album like '%${search}%') OR (ID = '${search}')
            ORDER BY v.rating DESC LIMIT 0, 10`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (!results.length) {
                message.msg(`\x0312Could not find any songs matching: \x0304${message}`);
            } else {
                for (let i = 0; i < results.length; i++) {
                    message.msg(songInfo(results[i]));
                }
            }
        });
        connection.end();
    });

    j.watch_for(/^!statsj$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const sql = 'SELECT max(listeners) as maxListeners from historylist LIMIT 0, 1';
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else {
                message.say(`\x0303The current listeners record is: \x0304 ${results[0].maxListeners} people at a time`);
            }
        });

        connection.end();
    });

    j.watch_for(/^!top5j$/, message => {
        const connection = mysql.createConnection(mysqlConfig);
        connection.connect();

        const filter = requestDays ? `AND (requestlist.t_stamp BETWEEN NOW() - INTERVAL ${requestDays} DAY AND NOW())` : '';
        const sql = `SELECT songlist.artist, songlist.title, songlist.duration, songlist.ID as songId, songlist.album, count(songlist.ID) as cnt, v.rating
            FROM requestlist, songlist
            left join (select avg(score) as rating, songId From votez Group By songId) as v On songlist.ID = v.songId
            WHERE (requestlist.songID = songlist.ID) AND (requestlist.code=200) ${filter}
            GROUP BY songlist.ID, songlist.artist, songlist.title ORDER BY cnt DESC Limit 0,5`;
        connection.query(sql, (error, results, fields) => {
            if (error) {
                handleMysqlError(error, message);
            } else if (results.length) {
                message.msg(`\x0304Most requested songs${requestDays ? ` in the last ${requestDays} days` : ''}:`);
                for (let i = 0; i < results.length; i++) {
                    message.msg(`\x0304Requested ${results[i].cnt} time(s): ` + songInfo(results[i]));
                }
            }
        });

        connection.end();
    });

    // vote, play
    // periodic check/dedication

}).connect(ircConfig);
