<?php

namespace Cysha\Casino\Bot;

use Cysha\Casino\Cards\CardCollection;
use Cysha\Casino\Cards\Deck;
use Cysha\Casino\Cards\Hand;
use Cysha\Casino\Cards\HandCollection;
use Cysha\Casino\Exceptions\CardException;
use Cysha\Casino\Game\Chips;
use Cysha\Casino\Game\Client;
use Cysha\Casino\Holdem\Cards\Evaluators\SevenCard;
use Cysha\Casino\Holdem\Game\Dealer;
use Cysha\Casino\Holdem\Game\Player;

class HoldemHandEvaluator
{
    public function evalHands(string $boardAndHands)
    {
        $hands = array_unique(explode('|', $boardAndHands));
        $board = array_shift($hands);

        if (count(explode(' ', $board)) !== 5) {
            return 'board doesnt contain 5 cards';
        }

        if (count($hands) == 1 && count(explode(' ', $hands[0])) == 2) {
            return $this->eval7cards(explode(' ', str_replace('|', ' ', $boardAndHands)));
        }

        $filteredHands = array_filter($hands, function ($hand) {
            return count(explode(' ', $hand)) === 2;
        });
        if (count($hands) !== count($filteredHands)) {
            return 'all hands must have 2 cards in';
        }

        try {
            $board = CardCollection::fromString($board);
            $handCollection = HandCollection::make();
            foreach ($hands as $idx => $hand) {
                $handCollection->push(Hand::createUsingString($hand, Player::fromClient(Client::register('player' . $idx, Chips::fromAmount(500)))));
            }

            $dealer = Dealer::startWork(new Deck(), new SevenCard());
            $result = $dealer->evaluateHands($board, $handCollection);

            if ($result !== null) {
                if ($result->count() > 1) {
                    return sprintf('%d way Split Pot - %s evals to %s', $result->count(), $result->first()->cards()->__toString(), $result->first()->definition());
                }

                return sprintf('%s wins with %s', $result->first()->cards()->__toString(), $result->first()->definition());
            }
        } catch (CardException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return 'Result came back null :/';
    }

    public function eval7cards(array $cards)
    {
        try {
            $hand = [
                array_pop($cards),
                array_pop($cards),
            ];
            $board = CardCollection::fromString(implode(' ', $cards));
            $hand = Hand::createUsingString(implode(' ', $hand), Player::fromClient(Client::register('xLink', Chips::fromAmount(500))));

            $result = SevenCard::evaluate($board, $hand);
        } catch (CardException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return sprintf('%s evals to %s', $result->cards()->__toString(), $result->definition());
    }
}
