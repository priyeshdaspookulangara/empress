package com.empress.twoway.models;

public class Withdrawal {
    private int id;
    private double amount;
    private String status;
    private String requestDate;

    public Withdrawal(int id, double amount, String status, String requestDate) {
        this.id = id;
        this.amount = amount;
        this.status = status;
        this.requestDate = requestDate;
    }

    public int getId() { return id; }
    public double getAmount() { return amount; }
    public String getStatus() { return status; }
    public String getRequestDate() { return requestDate; }
}
