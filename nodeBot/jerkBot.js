// https://github.com/gf3/Jerk
const jerk = require('jerk');
const Maria = require('mariasql');
const imdb = require('imdb-node-api');
const GoogleSearch = require('google-search');

const googleSearch = new GoogleSearch({
  key: 'AIzaSyDeAt7u8gbx5LRGnwI4d6ncmu_MkKPdEoE',
  cx: '011866386975486027187:85pfee1jvwu'
});

const options = {
  server: 'east.irc-reactor.com',
  nick: 'Jerk',
  channels: ['#sov']
};

jerk(j => {

  // j.watch_for( '!hello', function( message ) {
  //   message.say( message.user + ': Hello!' );
  //   message.say( JSON.stringify(message) );
  //   const c = new Maria({
  //     host: '192.168.1.25',
  //     user: 'irc-reactor',
  //     password: 'DbTcDoQHALqyQ3Cm'
  //   });

  //   c.query('SHOW DATABASES', function(err, rows) {
  //     if (err) {
  //       throw err;
  //     }
  //     message.say(JSON.stringify(rows));
  //   });

  //   c.end();
  // });

  // google search
  j.watch_for(/^!google (.+)$/, message => {
    googleSearch.build({
      q: message.match_data[1],
      num: 1
    }, (err, response) => {
      if (err) {
        message.say('\0034I have failed you.\0034 ' + err);
      } else if (response.error) {
        message.say('\0034I have failed you.\0034 ' + response.error.errors[0].reason);
      } else {
        const searchTime = response.searchInformation.formattedSearchTime;
        const totalResults = response.searchInformation.formattedTotalResults;
        const title = response.items[0].title;
        const link = response.items[0].link.replace(' ', '');
        const summary = response.items[0].snippet.replace(/\n/g, '');
        message.say('\0034Found ' + totalResults + ' results \0034\0037in ' + searchTime + ' seconds.\0037');
        message.say('\00312Top result: ' + title + '\00312');
        message.say('\0033Summary: ' + summary + '\0033');
        message.say('\00310Read more: ' + link + '\00310');
      }
    });
  });

  j.watch_for(/^!fight (.+)$/, message => {
    const full = message.match_data[1];
    const vsIndex = full.indexOf(' vs ');
    const contestant1 = full.substr(0, vsIndex);
    const contestant2 = full.substr(vsIndex + 4);
    if (vsIndex === -1 || !contestant1 || !contestant2 || contestant1 === contestant2) {
      message.say('Fight syntax is: <something> vs <something else>');
    } else if (contestant1.length <= 3 || contestant2.length <= 3) {
      message.say('Your contestant is too small to fight!');
    } else {
      // commence the fight
      const c1Promise = new Promise((resolve, reject) => {
        googleSearch.build({ q: contestant1, num: 1 }, (err, response) => {
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
        googleSearch.build({ q: contestant2, num: 1 }, (err, response) => {
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

      Promise.all([c1Promise, c2Promise]).then(results => {
        if (parseInt(results[0].totalResults) === parseInt(results[1].totalResults)) {
          message.say('\0034' + contestant1 + '\0034 and \0033' + contestant2 + '\0033 TIE with \00310' + results[0].formattedTotalResults + '\00310 total results!');
        } else if (parseInt(results[0].totalResults) > parseInt(results[1].totalResults)) {
          message.say('\0034' + contestant1 + '\0034 \00312BEATS\00312 \0033' + contestant2 + '\0033 with\00310 ' + results[0].formattedTotalResults + '\00310 to\0037 ' + results[1].formattedTotalResults + '\0037 total results!');
        } else {
          message.say('\0034' + contestant1 + '\0034 \00312LOSES TO\00312 \0033' + contestant2 + '\0033 with\00310 ' + results[0].formattedTotalResults + '\00310 to\0037 ' + results[1].formattedTotalResults + '\0037 total results!');
        }
      }).catch(err => {
        message.say('\0034I have failed you.\0034 ' + err);
      });
    }
  });

  // imdb (movie search)
  j.watch_for(/^!imdb (.+)$/, message => {
    imdb.search({ keyword: message.match_data[1], category: 'movie' }, (err, data) => {
      if (err) {
        message.say('\0034I have failed you.\0034 ' + err);
      } else {
        // we found something. Let's get details for the #1 result
        data = JSON.parse(data);
        const id = data.movies[0].imdbId;
        const title = data.movies[0].name;
        const year = data.movies[0].year;

        imdb.getByImdbId(id, (err2, data2) => {
          if (err2) {
            message.say('\0034I have failed you.\0034 ' + err2);
          } else {
            // we found details
            data2 = JSON.parse(data2);
            const rating = data2.movie.ratingValue;
            const genres = data2.movie.genre.join(', ');
            const summary = data2.movie.summaryText;
            const actors = data2.movie.actors.splice(0, 3).map(actor => actor.name).join(', ');
            message.say('[IMDB] \0034Movie: ' + title + '\0034 \00310(' + year + ')\00310 \00312Rating: ' + rating + '\00312');
            message.say('\0033Summary: ' + summary + '\0033');
            message.say('\0037Genres: ' + genres + '\0037 \0034Lead Actors: ' + actors + '\0034');
            message.say('\00310Read more: http://www.imdb.com/title/' + id + '\00310');
          }
        });
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
      message.say('No, fuck you');
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

}).connect(options);