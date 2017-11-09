# wp-polls-rest-api

This wordpress plugins permit to activate the rest api support for Wp-Polls Rest Api
original version by 7aduta  : https://gist.github.com/7aduta/2bfe5788fa2186255ebe1339ed01fb37

## Requests ##

#### wp-json/wp/v2/polls ####

GET all polls , is return a return array
> question (string name)
> > id
> > answers [array of object]
> > > polla_aid
> > > polla_answers
> > > polla_votes

####  wp-json/wp/v2/poll?id= ####
GET a poll, return a array

> poll [object]
> > id
> > answers [array of object]
> > > polla_aid
> > > polla_answers
> > > polla_votes
