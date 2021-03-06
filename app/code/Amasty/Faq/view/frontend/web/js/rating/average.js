define([
    'jquery',
    'underscore',
    'Amasty_Faq/js/rating/yes-no-voting',
    'jquery/jquery-storageapi'
], function ($, _, Voting) {
    return Voting.extend({
        defaults: {
            storageKey: 'amfaq-average-rating-storage',
            hideZeroRating: false,
            votedStarNumber: 0,
            votingBehavior: 'average',
            average: 0,
            total: 0
        },

        initialize: function () {
            this._super();

            var votedQuestions = $.localStorage.get(this.storageKey),
                questionId = this.id;

            if (_.isObject(votedQuestions) && !_.isUndefined(votedQuestions[questionId])) {
                this.votedStarNumber(votedQuestions[questionId]);
                this.isVoted(true);
            }

            this.votedStarNumber.subscribe(this.handleVoting.bind(this));

            return this;
        },

        initObservable: function () {
            this._super()
                .observe('votedStarNumber average');

            return this;
        },

        handleVoting: function (starNumber) {
            if (starNumber) {
                this.vote({starNumber: starNumber}, function () {
                    var votedQuestions = $.localStorage.get(this.storageKey),
                        questionId = this.id;

                    if (_.isNull(votedQuestions)) {
                        votedQuestions = {};
                    }

                    this.recalculateAverage(starNumber);
                    votedQuestions[questionId] = starNumber;
                    $.localStorage.set(this.storageKey, votedQuestions);
                }.bind(this));
            }
        },

        recalculateAverage: function (voteValue) {
            var total = this.total,
                average = parseFloat(this.average()),
                newAverage = (average * total + parseInt(voteValue)) / (total + 1);
            this.average(newAverage);
            this.total++;
        },
    });
});
