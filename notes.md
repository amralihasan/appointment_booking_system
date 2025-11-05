i want to create an appointment booking system for coaches and teachers, this is my vision, i want to convert it to plan then will go through the implementation
i will explain the system with example about Tennis coach
the coach will register in the platform , he will insert his first name and last name, mobile phone and email(option), whatsup number (required)
after register should be redirect to dashboard 'admin panel' , the coach have to some steps after register, we can redirect him to setting to complete the system configuration like:
    - working days
    - availability times like: he available on Saturday from 11:00 AM to 01:00 PM and from 04:00 PM to 06:30 PM and so on.
    - timezone 
    - Service like 'Private Training for 1 hour' so the service will be include:
        - name, description, price , duration, type [one,group] , slug
        the booking link will be like :https://domain-name.coach-name/service-name, the coach can change the slug for service but must be unique
- the system will content table for contacts, the table will include columns first name, last name, mobile phone, and email will be option
- the system will include table for appointments, 	service_id, user_id (coach), client_name, client_phone, date_time, status (booked, canceled, completed)
