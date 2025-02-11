<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Filters\V1\TicketFilter;
use App\Http\Requests\Api\V1\ReplaceTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\V1\TicketPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketController extends ApiController
{

    protected $policyClass = TicketPolicy::class;
    use AuthorizesRequests;

    /**
     * Get all tickets
     *
     * @group Managing Tickets
     * @queryParam sort string Data field(s) to sort by. Seperate multiple fields with commas. Denote descending sort with a minus sign. Example: sort=title, -createdAt
     * @queryParam filter[status] Filter by status code: A, C, H, X. No-example
     * @queryParam filter[title] Filter by title. Wildcards are supported. Example: *fix*
     */


    public function index(TicketFilter $filters)
    {
          //  $filters->status($value);

            return TicketResource::collection(Ticket::filter($filters)->paginate());
    }


    /**
     * Create a ticket
     *
     * Creates a new ticket. Users can only create tickets for themselves. Managers can create tickets for any user.
     *
     * @group Managing Tickets
     */
    public function store(StoreTicketRequest $request)
    {
            if($this->isAble('store', Ticket::class)) {
                return new TicketResource(Ticket::create($request->mappedAttributes()));
            }

            return $this->error('You are not authorized to create that resource', 401);
    }

    /**
     * Display the specified resource.
     */
    public function show($ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);
            if ($this->include('author')) {
                return new TicketResource($ticket->load('user'));

            }
            return new TicketResource($ticket);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Ticket not found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            if ($this->isAble('update', $ticket)) {
                $ticket->update($request->mappedAttributes());
                return new TicketResource($ticket);
            }

            return $this->error('You are not authorized to update that resource', 401);

        } catch (ModelNotFoundException $exception) {
            return $this->error('Ticket cannot be found.', 404);
        }

    }

    public function replace(ReplaceTicketRequest $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

           if($this->isAble('replace', $ticket)) {
               $ticket->update($request->mappedAttributes());
               return new TicketResource($ticket);
           }
            return $this->error('You are not authorized to update that resource', 401);


        } catch (ModelNotFoundException $exception) {
            return $this->error('Ticket cannot be found.', 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($ticket_id)
    {
        try {

            $ticket = Ticket::findOrFail($ticket_id);

            if($this->isAble('delete', $ticket))
            {
                $ticket->delete();
                return $this->ok('Ticket successfully deleted');
            }

            return $this->error('You are not authorized to destroy that resource', 401);

        } catch (ModelNotFoundException $exception) {
            return $this->error('Ticket not found', 404);
        }
    }
}
